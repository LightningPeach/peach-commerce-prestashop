{if (isset($status) && $status === 'ok')}
    <h3 class="lightninghub__title">
        {{l s='Your order on' mod='lightninghub'}}
        <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
        {{l s='is complete.' mod='lightninghub'}}
    </h3>
    <div class="lightninghub-order__container">
        <div class="lightninghub-order__left">
            <div class="lightninghub-order__row">
                    <span class="lightninghub-order__label">
                        {{l s='Amount' mod='lightninghub'}}:
                    </span>
                <span class="lightninghub-order__value">
                        {{$total|escape:'htmlall':'UTF-8'}} ~ {{$BTC|escape:'htmlall':'UTF-8'}}
                    </span>
            </div>
            <div class="lightninghub-order__row">
                    <span class="lightninghub-order__label">
                        {{l s='Reference' mod='lightninghub'}}:
                    </span>
                <span class="lightninghub-order__value">
                        {{$reference|escape:'html':'UTF-8'}}
                    </span>
            </div>
            <div class="lightninghub-order__row">
                    <span class="lightninghub-order__label">
                        {{l s='Order status' mod='lightninghub'}}:
                    </span>
                <span class="lightninghub-order__value">
                        {{$order_status|escape:'html':'UTF-8'}}
                    </span>
            </div>
            {if (!$settled && !$canceled)}
            <div class="lightninghub-order__row">
                    <span class="lightninghub-order__label">
                        {{l s='Invoice expire at' mod='lightninghub'}}:
                    </span>
                <span class="lightninghub-order__value">
                        <time id="lightning_expiry" data-expiry="{{$expiry_at|escape:'html':'UTF-8'}}"></time>
                    </span>
            </div>
            <div class="lightninghub-order__row">
                    <span class="lightninghub-order__label">
                        {{l s='Payment request' mod='lightninghub'}}:
                    </span>
            </div>
            <div class="lightninghub-order__row">
                <div class="lightninghub-order__container lightninghub-order__container--payreq">
                    <div class="lightninghub-order__left lightninghub-order__left--payreq">
                    <span class="lightninghub-order__value lightninghub-order__value--pay_req">
                        {{$payReq|escape:'htmlall':'UTF-8'}}
                    </span>
                    </div>
                    <div class="lightninghub-order__right">
                        <div
                                class="lightninghub-order__copyToClipboard"
                                data-copy="{{$payReq|escape:'htmlall':'UTF-8'}}"
                                title="Copy to clipboard"
                        >
                        </div>
                    </div>
                </div>
            </div>
            {/if}
        </div>
        {if (!$settled && !$canceled)}
        <div class="lightninghub-order__right">
            <div id="lightninghub__qrcode" data-generate="{{$payReq|escape:'htmlall':'UTF-8'}}"></div>
        </div>
        {/if}
    </div>
    {if (!$settled && !$canceled)}
    <div class="lightninghub-order__footer">
        <a class="btn btn-primary lightninghub-order__btn" href="{{$walletBtn|escape:'htmlall':'UTF-8'}}">
            Pay with Wallet
        </a>
        <span class="lightninghub-order__mail-notif">
                {{l s='An email has been sent with this information.' mod='lightninghub'}}
            </span>
    </div>
    {/if}
{else}
<h3 class="lightninghub__title">
    {{l s='Your order on' mod='lightninghub'}}
    <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
    {{l s='has not been accepted.' mod='lightninghub'}}
</h3>
<div class="lightninghub-order__container">
    <div class="lightninghub-order__row">
        <span class="lightninghub-order__label">
            {{l s='Reference' mod='lightninghub'}}:
        </span>
        <span class="lightninghub-order__value">
            {{$reference|escape:'html':'UTF-8'}}
        </span>
    </div>
</div>
<br/>{{l s='Please, try to order again.' mod='lightninghub'}}
<br/>{{l s='If you have questions, comments or concerns, please contact our' mod='lightninghub'}}
<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">
    {{l s='expert customer support team.' mod='lightninghub'}}
</a>
{/if}
<hr/>