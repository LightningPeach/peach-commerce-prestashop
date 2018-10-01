{if (isset($status) && $status === 'ok')}
<h3>
    {{l s='Your order on' mod='lightninghub'}}
    <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span>
    {{l s='is complete.' mod='lightninghub'}}
</h3>
<p>
    <br/>
    <span>- {{l s='Amount' mod='lightninghub'}}:</span>
    <span class="price">
        <strong>{{$total|escape:'htmlall':'UTF-8'}} ~ {{$BTC|escape:'htmlall':'UTF-8'}}</strong>
    </span>
    <br/>
    <span>- {{l s='Reference' mod='lightninghub'}}:</span>
    <span class="reference">
        <strong>{{$reference|escape:'html':'UTF-8'}}</strong>
    </span>
    <br/>
    <span>- {{l s='Order status' mod='lightninghub'}}:</span>
    <span class="reference">
        <strong>{{$order_status|escape:'html':'UTF-8'}}</strong>
    </span>
    {if (!$settled && !$canceled)}
        <br/>
        <span>- {{l s='Invoice expire at' mod='lightninghub'}}:</span>
        <span class="reference">
            <strong>
                <time id="lightning_expiry" data-expiry="{{$expiry_at|escape:'html':'UTF-8'}}"></time>
            </strong>
        </span>
        <br/>
        <span>- {{l s='Pay with wallet' mod='lightninghub'}}:</span>
        <a class="btn btn-primary" href="{{$walletBtn|escape:'htmlall':'UTF-8'}}">Pay</a>
        <br/>
        <span>- {{l s='QRCode' mod='lightninghub'}}:</span>
        <div id="lightninghub__qrcode" data-generate="{{$payReq|escape:'htmlall':'UTF-8'}}"></div>
        <br/>
        <span>- {{l s='Payment request' mod='lightninghub'}}:</span>
        <span class="lightninghub__break-word reference">
                <strong>{{$payReq|escape:'htmlall':'UTF-8'}}</strong>
            </span>
        <br/>
    {/if}
<br/>
{{l s='An email has been sent with this information.' mod='lightninghub'}}
<br/>
<br/>
</p>
{else}
<h3>{{l s='Your order on %s has not been accepted.' sprintf=$shop_name mod='lightninghub'}}</h3>
<p>
    <br/>- {{l s='Reference' mod='lightninghub'}} <span class="reference">
        <strong>{{$reference|escape:'html':'UTF-8'}}</strong>
    </span>
    <br/><br/>{{l s='Please, try to order again.' mod='lightninghub'}}
    <br/><br/>{{l s='If you have questions, comments or concerns, please contact our' mod='lightninghub'}}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">
        {{l s='expert customer support team.' mod='lightninghub'}}
    </a>
</p>
{/if}
<hr/>