

<!-- Medication Autocomplete (PHP + JS) -->

<script>
// Medication list from PHP
const medicationList = [
    <?php
    $hasCatalogueStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'rescue_medications'
          AND COLUMN_NAME = 'medication_name'
    ");
    $hasCatalogueStmt->execute();
    $hasMedicationCatalogue = (int)$hasCatalogueStmt->fetchColumn() > 0;

    $medStmt = $hasMedicationCatalogue
        ? $pdo->prepare("SELECT medication_name, COALESCE(common_name, '') AS common_name, COALESCE(class, '') AS class FROM rescue_medications ORDER BY class ASC, medication_name ASC")
        : $pdo->prepare("SELECT medication AS medication_name, '' AS common_name, '' AS class FROM rescue_medications ORDER BY medication ASC");
    $medStmt->execute();

    while ($row = $medStmt->fetch(PDO::FETCH_ASSOC)) {
        $label = addslashes($row["class"] . " - " . $row["medication_name"] . " (" . $row["common_name"] . ")");
        $value = addslashes($row["medication_name"]);
        echo "{ label: '{$label}', value: '{$value}' },";
    }
    ?>
];
</script>

<script>
function attachAutocomplete() {

    document.querySelectorAll(".medication_input").forEach(function (input) {

        // Prevent double binding
        if (input.dataset.autocompleteAttached === "1") return;
        input.dataset.autocompleteAttached = "1";

        const hidden = input.parentNode.querySelector(".medication_hidden");

        // CREATE CLEAR BUTTON ("×")
        const clearBtn = document.createElement("span");
        clearBtn.textContent = "×";
        clearBtn.style.position = "absolute";
        clearBtn.style.right = "10px";
        clearBtn.style.top = "34px";
        clearBtn.style.cursor = "pointer";
        clearBtn.style.fontWeight = "bold";
        clearBtn.style.color = "#900";
        clearBtn.style.display = "none";   // only after selection
        input.parentNode.appendChild(clearBtn);

        // AUTOCOMPLETE DROPDOWN BOX
        const box = document.createElement("div");
        box.style.position = "absolute";
        box.style.background = "white";
        box.style.border = "1px solid #ccc";
        box.style.zIndex = "9999";
        box.style.width = "100%";
        box.style.maxHeight = "150px";
        box.style.overflowY = "auto";
        box.style.boxShadow = "0 2px 4px rgba(0,0,0,0.2)";
        box.style.display = "none";
        input.parentNode.appendChild(box);

        // RESET FIELD (clear button function)
        clearBtn.addEventListener("click", () => {
            input.value = "";
            hidden.value = "";
            input.removeAttribute("readonly");
            input.classList.remove("valid-medication", "invalid-medication");
            clearBtn.style.display = "none";
        });

        // INPUT HANDLER
        input.addEventListener("input", function () {

            // User is typing → show invalid state & show dropdown
            hidden.value = "";
            input.classList.remove("valid-medication");
            input.classList.add("invalid-medication");

            const q = this.value.toLowerCase();
            box.innerHTML = "";

            if (!q) {
                box.style.display = "none";
                return;
            }

            const results = medicationList.filter(item =>
                item.label.toLowerCase().includes(q)
            );

            if (!results.length) {
                box.style.display = "none";
                return;
            }

            results.forEach(item => {

                const opt = document.createElement("div");
                opt.textContent = item.label;
                opt.style.padding = "6px 8px";
                opt.style.cursor = "pointer";

                opt.addEventListener("click", () => {

                    // Fill inputs
                    input.value = item.label;
                    hidden.value = item.value;

                    // Mark as valid
                    input.classList.remove("invalid-medication");
                    input.classList.add("valid-medication");

                    // Lock the field
                    input.setAttribute("readonly", "readonly");

                    // Show clear button
                    clearBtn.style.display = "block";

                    box.style.display = "none";
                });

                box.appendChild(opt);
            });

            box.style.display = "block";
        });

        // Click outside hides dropdown
        document.addEventListener("click", function (e) {
            if (!input.contains(e.target)) {
                box.style.display = "none";
            }
        });

    });
}

// Trigger immediately (not waiting for DOMContentLoaded)
attachAutocomplete();

// VALIDATION: Prevent form submission unless valid item chosen
/* VALIDATION: Ensure medication choice is from autocomplete,
   prevent multiple submit handlers firing repeatedly,
   and stop ALL stacked handlers using stopImmediatePropagation().
*/

if (!window.__medicationSubmitHandlerAttached__) {

    window.__medicationSubmitHandlerAttached__ = true;

    document.addEventListener("submit", function(e) {

        let invalidField = null;

        // Check all medication inputs
        document.querySelectorAll(".medication_input").forEach(input => {
            const hidden = input.parentNode.querySelector(".medication_hidden");

            // Visible input has text but no valid selection → invalid
            if (input.value.trim() !== "" && hidden.value.trim() === "") {
                invalidField = input;
            }
        });

        if (invalidField !== null) {

            // Stop this submission
            e.preventDefault();

            // Prevent ALL other submit handlers from firing
            e.stopImmediatePropagation();

            alert("Please select a medication from the list.");

            // Focus the problem field
            invalidField.focus();

            return;
        }

    }, true); // <-- IMPORTANT: capture mode so this runs before any duplicates
}


</script>

<style>
/* VALID / INVALID BORDER STYLES */

.invalid-medication {
    border: 2px solid #d9534f !important; /* red */
}

.valid-medication {
    border: 2px solid #5cb85c !important; /* green */
}
</style>
