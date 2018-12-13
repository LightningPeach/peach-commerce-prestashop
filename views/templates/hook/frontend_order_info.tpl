<section class="box">
    <h4>{l s='Payment details' mod='peachcommerce'}</h4>
    <div>
        <strong>{l s='Payment request' mod='peachcommerce'}:</strong> <span class="peachcommerce__break-word">{$payReq|escape:'htmlall':'UTF-8'}</span>
        <br/>
        <strong>{l s='Expiry at' mod='peachcommerce'}:</strong> <time id="lightning_expiry" data-expiry="{$expiry_at|escape:'htmlall':'UTF-8'}"></time>
    </div>
</section>