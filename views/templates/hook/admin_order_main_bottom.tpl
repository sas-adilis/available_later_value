<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-header-title">{l s='Date d\'expédition annoncée' mod='available_later_value'}</h3>
    </div>
    <div class="card-body">
        <form action="{$form_action}" method="post">
            <input type="hidden" name="id_order" value="{$id_order|intval}">
            <div class="form-group row type-choice">
                <label for="order_message_order_message" class="form-control-label label-on-top col-12">
                    {l s='Date d\'expédition annoncée' mod='available_later_value'}
                </label>

                <div class="col-12">
                    <div class="input-group datepicker">
                        <input type="text" class="form-control" data-format="YYYY-MM-DD" id="date_shipping_estimated" value="{if $date_shipping_estimated != '0000-00-00 00:00:00'}{$date_shipping_estimated|escape:'htmlall':'UTF-8'}{/if}" name="date_shipping_estimated" required="required">
                        <div class="input-group-append"><div class="input-group-text"><i class="material-icons">date_range</i></div></div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" name="submitDateShippingEstimated" class="btn btn-primary">Envoyer le message</button>
            </div>
        </form>
    </div>
</div>