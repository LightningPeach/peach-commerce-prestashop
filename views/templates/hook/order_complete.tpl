{if (isset($status) && $status === 'ok')}
<h3>
    {{l s='Your order on' mod='LightningHub'}}
    <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
    {{l s='is complete.' mod='LightningHub'}}
</h3>
<p>
    <br/>
    <span>- {{l s='Amount' mod='LightningHub'}}:</span>
    <span class="price">
        <strong>{{$total|escape:'htmlall':'UTF-8'}} ~ {{$BTC|escape:'htmlall':'UTF-8'}}</strong>
    </span>
    <br/>
    <span>- {{l s='Reference' mod='LightningHub'}}:</span>
    <span class="reference">
        <strong>{{$reference|escape:'html':'UTF-8'}}</strong>
    </span>
    <br/>
    <span>- {{l s='Order status' mod='LightningHub'}}:</span>
    <span class="reference">
        <strong>{{$order_status|escape:'html':'UTF-8'}}</strong>
    </span>
    {if (!$settled)}
        <br/>
        <span>- {{l s='Invoice expire at' mod='LightningHub'}}:</span>
        <span class="reference">
            <strong>
                <time id="lightning_expiry" data-expiry="{{$expiry_at|escape:'html':'UTF-8'}}"></time>
            </strong>
        </span>
        <br/>
        <span>- {{l s='Pay with wallet' mod='LightningHub'}}:</span>
        <a class="btn btn-primary" href="{{$walletBtn|escape:'htmlall':'UTF-8'}}">Pay</a>
        <br/>
        <span>- {{l s='QRCode' mod='LightningHub'}}:</span>
        <div id="lightninghub__qrcode" data-generate="{{$payReq|escape:'htmlall':'UTF-8'}}"></div>
        <br/>
        <span>- {{l s='Payment request' mod='LightningHub'}}:</span>
        <span class="lightninghub__break-word reference">
                <strong>{{$payReq|escape:'htmlall':'UTF-8'}}</strong>
            </span>
        <br/>
    {/if}
<br/>
{{l s='An email has been sent with this information.' mod='LightningHub'}}
<br/>
<br/>
</p>
{else}
<h3>{{l s='Your order on %s has not been accepted.' sprintf=$shop_name mod='LightningHub'}}</h3>
<p>
    <br/>- {{l s='Reference' mod='LightningHub'}} <span class="reference">
        <strong>{{$reference|escape:'html':'UTF-8'}}</strong>
    </span>
    <br/><br/>{{l s='Please, try to order again.' mod='LightningHub'}}
    <br/><br/>{{l s='If you have questions, comments or concerns, please contact our' mod='LightningHub'}}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">
        {{l s='expert customer support team.' mod='LightningHub'}}
    </a>
</p>
{/if}
<hr/>