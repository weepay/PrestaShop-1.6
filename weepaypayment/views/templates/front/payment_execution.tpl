
{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='weepaypayment'}">{l s='Checkout' mod='weepaypayment'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='weepay Payment' mod='weepaypayment'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='weepaypayment'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='weepaypayment'}</p>
{else}

<div style="    border: 1px solid #d6d4d4;
    padding: 14px 18px 13px;
    margin: 0 0 30px 0;
    line-height: 23px;">
<h3>{l s='Credit Card Form' mod='weepaypayment'}</h3>
<p>
	<img src="{$this_path_bw}/img/weepay.png" alt="{l s='weepay Payment' mod='weepaypayment'}"  height="30" style="float:left; margin: 0px 10px 5px 0px;" />

	<br/><br />
	
</p>
<div class= "row"> 
    <div class="col-xs-12">
        {if (isset($error)) }
        <div class="paiement_block">
            <p class="alert alert-warning">{$error}</p>
        </div>
        {else}
        {if ($form_class == 'popup')}
        <div id="weePay-checkout-form"  class="popup"> {$response}</div>  
        {else}
        <div id="weePay-checkout-form" class="responsive"> {$response}</div>  
        {/if}
        {/if}
        {if (isset($currency_error) && $currency_error != '')}
        <p class="alert alert-warning">{$currency_error}</p> 
        {/if}
    </div>
</div>

<p>

</p>

</div>

{/if}
