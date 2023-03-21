<!-- Activate -->
<div class="modal fade" id="activate">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Activating...</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="pending_change.php">
          <input type="hidden" class="userid" name="id">
          <div class="text-center">
            <p>Change Status</p>
            <!-- <h2 class="bold fullname"></h2> -->
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-success btn-flat" name="activate"><i class="fa fa-check"></i> Activate</button>
        </form>
      </div>
    </div>
  </div>
</div>
