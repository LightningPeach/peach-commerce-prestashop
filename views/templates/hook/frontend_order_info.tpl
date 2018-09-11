<section class="box">
    <h4>Payment details</h4>
    <div>
        <strong>Payment request:</strong> <span class="lightninghub__break-word">{{$payReq|escape:'html':'UTF-8'}}</span>
        <br/>
        <strong>Expiry at:</strong> <time id="lightning_expiry" data-expiry="{{$expiry_at|escape:'html':'UTF-8'}}"></time>
    </div>
</section>