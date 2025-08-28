<input type="hidden" name="worldline-pay-by-link-title"
       value="{html_entity_decode($worldlinePayByLinkTitle|escape:'html':'UTF-8')}">

<div class="form-group row type-text" id="worldline-expires-at" style="display: none">
    <label for="worldline-expires-at" class="form-control-label">
        <span class="text-danger">*</span>
        {l s='Payment link expires at' mod='worldlineop'}
    </label>
    <div class="col-sm-2">
        <input type="date" class="form-control"
               id="worldline-expires-at-date" name="worldline-expires-at-date"
               value="{html_entity_decode($worldlineExpirationDate|escape:'html':'UTF-8')}"
               aria-label="customer_date_add_from input"
               style="text-align: center">
    </div>
</div>
