<div class="modal fade chat_page_modal send_tips_modal" id="send_tips_modal" tabindex="-1" aria-labelledby="send_tips_modal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo Registry::load('strings')->send_tip; ?></h5>
            </div>
            <div class="modal-body">
                <form class="send_tip_form no_form_submit">

                    <div class="mb-4">
                        <div class="error"></div>
                        <label for="tip_amount" class="form-label">
                            <?php echo Registry::load('strings')->tip_amount.' ('.Registry::load('settings')->default_currency_symbol.')'; ?> :
                        </label>
                        <input type="number" name="tip_amount" class="tip_amount" placeholder="<?php echo Registry::load('strings')->enter_amount ?>" required>
                        <br>
                        <label for="tip_amount" class="form-label">
                            <?php echo Registry::load('strings')->tip_message; ?> :
                        </label>
                        <textarea name="tip_message" class="tip_message" placeholder="<?php echo Registry::load('strings')->tip_message ?>"></textarea>
                        <input type="hidden" name="tip_user_id" class="tip_user_id">
                        <input type="hidden" name="add" value="user_tips">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Registry::load('strings')->close; ?></button>
                <button type="button" class="btn btn-primary api_request" output_message_field='.send_tips_modal form .error' form_data='.send_tips_modal .send_tip_form' withdraw="true">
                    <?php echo Registry::load('strings')->send_tip; ?>
                </button>
            </div>
        </div>
    </div>
</div>