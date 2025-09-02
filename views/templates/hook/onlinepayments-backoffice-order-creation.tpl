<input type="hidden" name="{$moduleName}-pay-by-link-title"
       value="{html_entity_decode($pluginPayByLinkTitle|escape:'html':'UTF-8')}">

<div class="form-group row type-text" id="{$moduleName}-expires-at" style="display: none">
    <label for="{$moduleName}-expires-at" class="form-control-label">
        <span class="text-danger">*</span>
        {l s='Payment link expires at' mod="{$moduleName}"}
    </label>
    <div class="col-sm-2">
        <input type="date" class="form-control"
               id="{$moduleName}-expires-at-date" name="{$moduleName}-expires-at-date"
               value="{html_entity_decode($pluginExpirationDate|escape:'html':'UTF-8')}"
               aria-label="customer_date_add_from input"
               style="text-align: center">
    </div>
</div>


<script>
    $(document).ready(function () {
        adjustBackOfficeOrderCreation("{$moduleName}");
        paymentLinkCopier("{$moduleName}");
    });
</script>
