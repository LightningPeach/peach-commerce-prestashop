<div class="bootstrap hidden" id="peachcommerce__error">
    <div class="module_error alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <span class="peachcommerce__error-text"></span>
    </div>
</div>
<div class="bootstrap hidden" id="peachcommerce__success">
    <div class="module_error alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <span class="peachcommerce__success-text"></span>
    </div>
</div>
<div class="panel">
    <h3><i class="icon-bookmark"></i> Data</h3>
    <div class="row">
        <div class="col-lg-2">
            {l s='Balance' mod='peachcommerce'}
        </div>
        <div class="col-lg-10">
            <span class="peachcommerce__balance-value">{$balance|escape:'htmlall':'UTF-8'}</span>
        </div>
    </div>
    <div class="panel-footer">
        <button type="button" id="peachcommerce__balance-submit" class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Withdraw' mod='peachcommerce'}
        </button>
    </div>
</div>
<div class="clear"><br/></div>
<script>
    $(document).ready(function () {
        var $error = $('#peachcommerce__error');
        var $success = $('#peachcommerce__success');
        $(document).on('click', '#peachcommerce__balance-submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: "{$current|escape:'html':'UTF-8'}".replace(/&amp;/g,'&'),
                method: 'post',
                dataType: 'json',
                data: {
                    controller: 'AdminPeachCommerce',
                    action: 'withdraw',
                    ajax: true,
                    token: '{$token}'
                },
                beforeSend: function () {
                    $error.find('.peachcommerce__error-text').text('').end().addClass('hidden');
                    $success.find('.peachcommerce__success-text').text('').end().addClass('hidden');
                },
                success: function (data) {
                    if (!data.ok) {
                        $error.find('.peachcommerce__error-text').text(data.error).end().removeClass('hidden');
                        return;
                    }
                    $success.find('.peachcommerce__success-text').text('{l s="Funding transaction hash:" mod="peachcommerce"} ' + data.tx_hash).end().removeClass('hidden');
                    $('.peachcommerce__balance-value').text(data.balance);
                }
            })
        })
    })
</script>