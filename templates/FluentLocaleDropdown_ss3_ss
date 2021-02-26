<% if Locales %>
<div class="localepicker btn-group">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <!--$LocaleInformation.Alias-->
        $CurrentLocaleInformation.Alias
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <% loop Locales %>
        <li class="$LinkingMode">
            <a href="$Link.ATT" <% if $LinkingMode != 'invalid' %>rel="alternate" hreflang="$LocaleRFC1766"<% end_if %>><!--$Title.XML-->$LanguageNative</a>
        </li>
        <!--<li class="langswitch $LinkingMode $Alias">
                                        <a href="$Link.ATT" <% if $LinkingMode != 'invalid' %>rel="alternate" hreflang="$LocaleRFC1766"<% end_if %>>$LanguageNative</a>
                                </li>-->
        <% end_loop %>
    </ul>
</div>
<% end_if %>