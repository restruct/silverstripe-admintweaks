<div class="field copy-text-field input-group"<% if $ShowAlert %> data-copy-alert="$AlertMessage"<% end_if %>>
    <input type="text"
           id="$ID"
           name="$Name"
           class="form-control"
           value="$Value"
           <% if $IsReadonly %>readonly<% end_if %>
           <% if $MaxLength %>maxlength="$MaxLength"<% end_if %>
           <% if $isDisabled %>disabled<% end_if %>>
    <div class="input-group-append">
        <button class="$ButtonClasses copy-text-field__btn" type="button" title="<% if $ButtonTitle %>$ButtonTitle<% else %><%t CopyTextField.Copy 'Copy to clipboard' %><% end_if %>">
            <%-- Copy icon (bi-copy) --%>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="copy-text-field__icon-copy" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
            </svg>
            <%-- Check icon (bi-check2) - hidden by default --%>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="copy-text-field__icon-check" viewBox="0 0 16 16" style="display: none;">
                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
            </svg>
            <% if $ButtonLabel %><span class="copy-text-field__label ml-1 ms-1">$ButtonLabel</span><% end_if %>
        </button>
    </div>
</div>
