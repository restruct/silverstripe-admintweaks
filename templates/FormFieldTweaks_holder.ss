<%--                <div class="form-floating">--%>
<%--                    <input type="text" class="form-control" id="floatingPassword" placeholder="Password">--%>
<%--                    <label for="floatingPassword">Password</label>--%>
<%--                </div>--%>
<div class="form-group group-{$Name} _has-validation" $HolderAttributesHTML>

    <div class="holder-{$Name} form-floating <% if $Append || $AppendTxt %>input-group<% end_if %> <% if $MessageType=='good' %>is-valid<% else_if $MessageType=='bad' %>is-invalid<% end_if %>">
        $Field
        <% if $Title %><label for="$ID">$Title</label><% end_if %>
        <% if $AppendTxt %><div class="input-group-append"><span class="input-group-text">$AppendTxt</span></div><% end_if %>
        <% if $Append %>$Append<% end_if %>
    </div>
<%--    <% if $RightTitle %><small class="form-text text-muted" id="$ID">$RightTitle</small><% end_if %>--%>
    <% if $Description && not $Message %><small class="description text-muted">$Description</small><% end_if %>
    <% if $Message %><small class="validation-$MessageType <% if $MessageType=='good' %>valid-feedback<% else %>invalid-feedback<% end_if %>">$Message</small>
    <% end_if %>

</div>
