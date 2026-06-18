-- Learning Module Database Schema

-- Courses table
CREATE TABLE IF NOT EXISTS rescue_learning_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_centre_id INT NOT NULL,
    created_by_user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    pass_mark_percent TINYINT DEFAULT 70,
    max_attempts TINYINT DEFAULT 3,
    visibility ENUM('private', 'centre', 'global') DEFAULT 'private',
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner (owner_centre_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course pages/content
CREATE TABLE IF NOT EXISTS rescue_learning_pages (
    page_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    page_title VARCHAR(255) NOT NULL,
    page_type ENUM('text', 'video', 'file', 'link', 'mixed') DEFAULT 'text',
    page_content LONGTEXT,
    media_url VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_required TINYINT DEFAULT 1,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES rescue_learning_courses(course_id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assessments (one per course)
CREATE TABLE IF NOT EXISTS rescue_learning_assessments (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    instructions TEXT,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES rescue_learning_courses(course_id) ON DELETE CASCADE,
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions for assessments
CREATE TABLE IF NOT EXISTS rescue_learning_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('single_choice', 'multi_choice', 'true_false', 'yes_no', 'yes_no_unknown') DEFAULT 'single_choice',
    sort_order INT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES rescue_learning_assessments(assessment_id) ON DELETE CASCADE,
    INDEX idx_assessment (assessment_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answer options for questions
CREATE TABLE IF NOT EXISTS rescue_learning_answers (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct TINYINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES rescue_learning_questions(question_id) ON DELETE CASCADE,
    INDEX idx_question (question_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course suites/collections
CREATE TABLE IF NOT EXISTS rescue_learning_suites (
    suite_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_centre_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner (owner_centre_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relationship: courses to suites
CREATE TABLE IF NOT EXISTS rescue_learning_suite_courses (
    suite_course_id INT AUTO_INCREMENT PRIMARY KEY,
    suite_id INT NOT NULL,
    course_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (suite_id) REFERENCES rescue_learning_suites(suite_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES rescue_learning_courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_suite_course (suite_id, course_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course assignments (to roles or users)
CREATE TABLE IF NOT EXISTS rescue_learning_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    course_id INT NOT NULL,
    target_type ENUM('role', 'user') NOT NULL,
    target_id INT NOT NULL,
    assigned_by_user_id INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    is_active TINYINT DEFAULT 1,
    INDEX idx_course (course_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (course_id) REFERENCES rescue_learning_courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learner progress tracking
CREATE TABLE IF NOT EXISTS rescue_learning_user_courses (
    user_course_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    centre_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed', 'failed') DEFAULT 'not_started',
    assessment_status ENUM('not_attempted', 'passed', 'failed') DEFAULT 'not_attempted',
    assessment_score TINYINT,
    progress_percent TINYINT DEFAULT 0,
    current_page_id INT,
    attempt_count TINYINT DEFAULT 0,
    certificate_earned TINYINT DEFAULT 0,
    started_at DATETIME,
    completed_at DATETIME,
    passed_at DATETIME,
    last_accessed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_course (user_id, course_id, centre_id),
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-page progress tracking
CREATE TABLE IF NOT EXISTS rescue_learning_page_progress (
    page_progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_course_id INT NOT NULL,
    page_id INT NOT NULL,
    is_viewed TINYINT DEFAULT 1,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    time_spent_seconds INT DEFAULT 0,
    UNIQUE KEY unique_user_page (user_course_id, page_id),
    INDEX idx_user_course (user_course_id),
    INDEX idx_page (page_id),
    FOREIGN KEY (user_course_id) REFERENCES rescue_learning_user_courses(user_course_id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES rescue_learning_pages(page_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User assessment answers
CREATE TABLE IF NOT EXISTS rescue_learning_user_answers (
    user_answer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT NOT NULL,
    user_course_id INT,
    is_correct TINYINT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES rescue_learning_questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES rescue_learning_answers(answer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_course_id) REFERENCES rescue_learning_user_courses(user_course_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_question (question_id),
    INDEX idx_user_course (user_course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificates earned
CREATE TABLE IF NOT EXISTS rescue_learning_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_course_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(50) UNIQUE,
    earned_at DATETIME NOT NULL,
    expires_at DATETIME,
    is_valid TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_course_id) REFERENCES rescue_learning_user_courses(user_course_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES rescue_learning_courses(course_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_code (certificate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- UPDATE: Additional assessment question types
-- For existing learning-module installs only.
-- ---------------------------------------------------------------------

ALTER TABLE rescue_learning_questions
    MODIFY question_type ENUM('single_choice', 'multi_choice', 'true_false', 'yes_no', 'yes_no_unknown') DEFAULT 'single_choice';
