<?php ?>
<!-- Modal -->
<div class="modal" id="wraModal" tabindex="99999999" role="dialog" aria-labelledby="wraModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="wraModalLabel">How the WRA (wildlife rapid assessment) Score is calculated</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
<div class="modal-body">
    A collaborative approach has been undertaken to provide rescues with a rapid, traffic light system for you to consider when triaging your patients. 
<BR>At the moment Injury assessment, Age and body condition are used to factor the score.
   <BR> <BR>
<div class="table-responsive">
<table class="table">
    <thead class="thead-dark">
        <tr>
            <th>Variable</th>
            <th>Score 0</th>
            <th>Score 1</th>
            <th>Score 2</th>
            <th>Score 3</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><h6><p class="text-dark">Age</p></h6></td>
            <td class="bg-success"><h6><p class="text-white">Adult</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Independent Juvenile</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Dependent Juvenile</p></h6</td>
            <td class="bg-danger"><h6><p class="text-white">Newborn</p></h6></td>
        </tr>
        <tr>
            <td><h6><p class="text-dark">Injury Assessment</p></h6></td>
            <td class="bg-success"><h6><p class="text-white">Healthy/Mild Injuries</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Obvious Injuries</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Severe Injuries</p></h6></td>
            <td class="bg-danger"><h6><p class="text-white">Near Death</p></h6></td>
        </tr>
        <tr>
            <td><h6><p class="text-dark">Body Condition Score</p></h6></td>
            <td class="bg-success"><h6><p class="text-white">Healthy or Overweight (BCS 4 or 5)</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Slightly underweight (BCS 3)</p></h6></td>
            <td class="bg-warning"><h6><p class="text-dark">Underweight (BCS 2)</p></h6></td>
            <td class="bg-danger"><h6><p class="text-white">Emacited/Skeletal (BCS 1)</p></h6></td>
        </tr>
        
        </tbody>  
</table>
</div>

<BR>
<p class="text-dark">While scores factor variables to give likely prognosis and can be cumulative, it is also worth paying attention to the areas if a single 
  score of 3 was reached. Single parameters where 3 was scored could also indicate a poor prognosis.</p>
</p>

      </div>
      <div class="modal-footer">
      <p class="text-danger"><strong>The WRA Score is just another tool and is not designed to replace your judgement.</p>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>
// Get the modal
var modal = document.getElementById("wraModal");
// Get the button that opens the modal
var btn = document.getElementById("wraBtn");
// Get the <span> element that closes the modal
var span = document.getElementsByClassName("hidemodal")[0];
// When the user clicks the button, open the modal 
btn.onclick = function() {
  modal.style.display = "block";
}
// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}
// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
