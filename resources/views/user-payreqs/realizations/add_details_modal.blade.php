<div class="modal fade" id="add_detail">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Detail</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('user-payreqs.realizations.add_detail_store') }}" method="POST">
                @csrf
                <input type="hidden" name="realization_id" value="{{ $realization->id }}">

                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    
                </div>

            </form>
        </div>
    </div>
</div>