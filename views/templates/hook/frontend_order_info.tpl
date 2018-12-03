<section class="box">
    <h4>{l s='Payment details' mod='lightninghub'}</h4>
    <div>
        <strong>{l s='Payment request' mod='lightninghub'}:</strong> <span class="lightninghub__break-word">{$payReq|escape:'htmlall':'UTF-8'}</span>
        <br/>
        <strong>{l s='Expiry at' mod='lightninghub'}:</strong> <time id="lightning_expiry" data-expiry="{$expiry_at|escape:'htmlall':'UTF-8'}"></time>
    </div>
</section>