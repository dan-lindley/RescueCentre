<?php
class DashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getSpeciesList($centre_id) {
        $sql = "SELECT DISTINCT animal_species 
                  FROM rescue_patients 
                 WHERE centre_id = :centre_id 
              ORDER BY animal_species";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':centre_id' => $centre_id]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'animal_species');
    }

    public function getDispositionList() {
        $sql = "SELECT DISTINCT disposition 
                  FROM rescue_admissions 
                 WHERE disposition IS NOT NULL 
              ORDER BY disposition";
        $stmt = $this->db->query($sql);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'disposition');
    }

    public function getSpeciesBreakdown($filters) {
        $params = [];
        $sql = "SELECT rp.animal_species, COUNT(*) as total 
                  FROM rescue_patients rp";
        $sql = $this->applyFilters($sql, $filters, $params);
        $sql .= " GROUP BY rp.animal_species ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getMonthlyAdmissions($filters) {
        $params = [];
        $sql = "SELECT MONTH(ra.admission_date) as month, COUNT(*) as total 
                  FROM rescue_admissions ra
                 INNER JOIN rescue_patients rp 
                         ON ra.patient_id = rp.patient_id";
        $sql = $this->applyFilters($sql, $filters, $params);
        $sql .= " GROUP BY MONTH(ra.admission_date) ORDER BY month ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyDispositions($filters) {
        $params = [];
        $sql = "SELECT MONTH(ra.admission_date) as month, ra.disposition, COUNT(*) as total 
                  FROM rescue_admissions ra
                 INNER JOIN rescue_patients rp 
                         ON ra.patient_id = rp.patient_id";
        $sql = $this->applyFilters($sql, $filters, $params);
        $sql .= " GROUP BY MONTH(ra.admission_date), ra.disposition 
                  ORDER BY month ASC, ra.disposition ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   private function applyFilters($baseSql, $filters, &$params) {
    $where = [];

    // Detect if the query includes the admissions table
    $hasAdmissions = strpos($baseSql, 'rescue_admissions') !== false;

    // Filter by centre (always in rescue_patients)
    if (!empty($filters['centre_id'])) {
        $where[] = "rp.centre_id = :centre_id";
        $params[':centre_id'] = $filters['centre_id'];
    }

    // Filter by year — only if rescue_admissions is in the query
    if (!empty($filters['year']) && $hasAdmissions) {
        $where[] = "YEAR(ra.admission_date) = :year";
        $params[':year'] = $filters['year'];
    }

    // Filter by species (always in rescue_patients)
    if (!empty($filters['species']) && is_array($filters['species'])) {
        $phs = [];
        foreach ($filters['species'] as $i => $s) {
            $key = ":species$i";
            $phs[] = $key;
            $params[$key] = $s;
        }
        $where[] = "rp.animal_species IN (" . implode(",", $phs) . ")";
    }

    // Filter by disposition — only if admissions table is present
    if (!empty($filters['disposition']) && is_array($filters['disposition']) && $hasAdmissions) {
        $phs = [];
        foreach ($filters['disposition'] as $i => $d) {
            $key = ":dispo$i";
            $phs[] = $key;
            $params[$key] = $d;
        }
        $where[] = "ra.disposition IN (" . implode(",", $phs) . ")";
    }

    // Combine WHERE parts
    if ($where) {
        $baseSql .= " WHERE " . implode(" AND ", $where);
    }

    return $baseSql;
}

}
