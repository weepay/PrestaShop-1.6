<div class= "row"> 
    <div class="col-xs-12">

        <p class="payment_module">
<style>
p.payment_module a.weepaypayment {
    background: url({$module_dir}img/weepay.png) 15px 12px no-repeat #fbfbfb;
</style>   <a class="weepaypayment" href="javascript:toggleform();" title="{$credit_card}">
            </a>
        </p>
        {if (isset($error)) }
        <div class="paiement_block">
            <p class="alert alert-warning">{$error}</p>
        </div>
        {else}
        {if ($form_class == 'popup')}
        <div id="weePay-checkout-form" style="display: none;" class="popup"> {$response}</div>  
        {else}
        <div id="weePay-checkout-form" class="responsive" style="display: none;"> {$response}</div>  
        {/if}
        {/if}
        {if (isset($currency_error) && $currency_error != '')}
        <p class="alert alert-warning">{$currency_error}</p> 
        {/if}
    </div>
</div>

{literal}
<script type="text/javascript">
    function toggleform() {
        var ele = document.getElementById("weePay-checkout-form");

        if (ele.style.display == "block") {
            ele.style.display = "none";
        }
        else {
            ele.style.display = "block";
        }
    }
</script>
{/literal}