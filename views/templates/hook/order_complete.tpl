{if (isset($status) && $status === 'ok')}
    <h3 class="peachcommerce__title">
        {{l s='Your order on' mod='peachcommerce'}}
        <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
        {{l s='is ready.' mod='peachcommerce'}}
    </h3>
    <div class="peachcommerce-order__container">
        <div class="peachcommerce-order__left">
            <div class="peachcommerce-order__row">
                    <span class="peachcommerce-order__label">
                        {{l s='Amount' mod='peachcommerce'}}:
                    </span>
                <span class="peachcommerce-order__value">
                        {$total|escape:'htmlall':'UTF-8'} ~ {$BTC|escape:'htmlall':'UTF-8'}
                    </span>
            </div>
            <div class="peachcommerce-order__row">
                    <span class="peachcommerce-order__label">
                        {l s='Reference' mod='peachcommerce'}:
                    </span>
                <span class="peachcommerce-order__value">
                        {$reference|escape:'htmlall':'UTF-8'}
                    </span>
            </div>
            <div class="peachcommerce-order__row">
                    <span class="peachcommerce-order__label">
                        {{l s='Order status' mod='peachcommerce'}}:
                    </span>
                <span class="peachcommerce-order__value">
                        {$order_status|escape:'htmlall':'UTF-8'}
                    </span>
            </div>
            {if (!$settled && !$canceled)}
            <div class="peachcommerce-order__row">
                    <span class="peachcommerce-order__label">
                        {{l s='Invoice expire at' mod='peachcommerce'}}:
                    </span>
                <span class="peachcommerce-order__value">
                        <time id="lightning_expiry" data-expiry="{$expiry_at|escape:'htmlall':'UTF-8'}"></time>
                    </span>
            </div>
            <div class="peachcommerce-order__row">
                    <span class="peachcommerce-order__label">
                        {{l s='Payment request' mod='peachcommerce'}}:
                    </span>
            </div>
            <div class="peachcommerce-order__row">
                <div class="peachcommerce-order__container peachcommerce-order__container--payreq">
                    <div class="peachcommerce-order__left peachcommerce-order__left--payreq">
                    <span class="peachcommerce-order__value peachcommerce-order__value--pay_req">
                        {$payReq|escape:'htmlall':'UTF-8'}
                    </span>
                    </div>
                    <div class="peachcommerce-order__right">
                        <div
                                class="peachcommerce-order__copyToClipboard"
                                data-copy="{$payReq|escape:'htmlall':'UTF-8'}"
                                title="Copy to clipboard"
                        >
                        </div>
                    </div>
                </div>
            </div>
            {/if}
            {if $settled}
            <div class="peachcommerce-order__row">
                {l s='You will receive a confirmation email if the payment is processed successfully.' mod='peachcommerce'}
            </div>
            {/if}
        </div>
        {if (!$settled && !$canceled)}
        <div class="peachcommerce-order__right">
            <div id="peachcommerce__qrcode" data-generate="{$payReq|escape:'htmlall':'UTF-8'}"></div>
        </div>
        {/if}
    </div>
    {if (!$settled && !$canceled)}
    <div class="peachcommerce-order__footer">
        <a class="btn btn-primary peachcommerce-order__btn" href="{$walletBtn|escape:'htmlall':'UTF-8'}">
            {l s='Pay with Wallet' mod='peachcommerce'}
        </a>
        <span class="peachcommerce-order__mail-notif">
            {l s='Thank\'s for your order! Details of the order will be sent to your email.' mod='peachcommerce'}
        </span>
    </div>
    <script>
        function checkStatus(){
            $.ajax({
                type: "GET",
                url: "{$status_link|escape:'html':'UTF-8'}".replace(/&amp;/g,'&'),
                cache: false,
                dataType: 'json',
                data: {
                    order_id: "{$id_order|escape:'htmlall':'UTF-8'}",
                    token: "{$static_token|escape:'htmlall':'UTF-8'}",
                },
                success: function (data) {
                    if (data.reload) {
                        location.reload();
                    }
                }
            })
        }
        document.addEventListener("DOMContentLoaded", function () {
            checkStatus();
            setInterval(function(){
                checkStatus();
            }, 30000);
        })
    </script>
    {/if}
{else}
<h3 class="peachcommerce__title">
    {{l s='Your order on' mod='peachcommerce'}}
    <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
    {{l s='has not been accepted.' mod='peachcommerce'}}
</h3>
<div class="peachcommerce-order__container">
    <div class="peachcommerce-order__row">
        <span class="peachcommerce-order__label">
            {{l s='Reference' mod='peachcommerce'}}:
        </span>
        <span class="peachcommerce-order__value">
            {$reference|escape:'htmlall':'UTF-8'}
        </span>
    </div>
</div>
<br/>{{l s='Please, try to order again.' mod='peachcommerce'}}
<br/>{{l s='If you have questions, comments or concerns, please contact our' mod='peachcommerce'}}
<a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">
    {{l s='expert customer support team.' mod='peachcommerce'}}
</a>
{/if}
<hr/>