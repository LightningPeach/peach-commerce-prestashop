<div class="bootstrap hidden" id="lightninghub__error">
    <div class="module_error alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <span class="lightninghub__error-text"></span>
    </div>
</div>
<div class="bootstrap hidden" id="lightninghub__success">
    <div class="module_error alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <span class="lightninghub__success-text"></span>
    </div>
</div>
<div class="panel">
    <h3><i class="icon-bookmark"></i> Data</h3>
    <div class="row">
        <div class="col-lg-2">
            {l s='Balance' mod='lightninghub'}
        </div>
        <div class="col-lg-10">
            <span class="lightninghub__balance-value">{$balance|escape:'htmlall':'UTF-8'}</span>
        </div>
    </div>
    <div class="panel-footer">
        <button type="button" id="lightninghub__balance-submit" class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Withdraw' mod='lightninghub'}
        </button>
    </div>
</div>
<div class="clear"><br/></div>
<script>
    $(document).ready(function () {
        var $error = $('#lightninghub__error');
        var $success = $('#lightninghub__success');
        $(document).on('click', '#lightninghub__balance-submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: "{$current|escape:'html':'UTF-8'}".replace(/&amp;/g,'&'),
                method: 'post',
                dataType: 'json',
                data: {
                    controller: 'AdminLightningHub',
                    action: 'withdraw',
                    ajax: true,
                    token: '{$token}'
                },
                beforeSend: function () {
                    $error.find('.lightninghub__error-text').text('').end().addClass('hidden');
                    $success.find('.lightninghub__success-text').text('').end().addClass('hidden');
                },
                success: function (data) {
                    if (!data.ok) {
                        $error.find('.lightninghub__error-text').text(data.error).end().removeClass('hidden');
                        return;
                    }
                    $success.find('.lightninghub__success-text').text('{l s="Funding transaction hash:" mod="lightninghub"} ' + data.tx_hash).end().removeClass('hidden');
                    $('.lightninghub__balance-value').text(data.balance);
                }
            })
        })
    })
</script>