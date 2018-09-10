<div class="panel">
    <h3><i class="icon icon-tags"></i> {l s='Additional info' mod='LightningHub'}</h3>
    <p>
        &raquo; {l s="You'r balance is"} : {$balance|escape:'htmlall':'UTF-8'} {l s="Satoshi"}
    </p>
    <p>
        &raquo; {l s="Url for 'notification-url' callback"} :
    <ul>
        <li>{$notificationUrl|escape:'htmlall':'UTF-8'}</li>
    </ul>
    </p>
</div>