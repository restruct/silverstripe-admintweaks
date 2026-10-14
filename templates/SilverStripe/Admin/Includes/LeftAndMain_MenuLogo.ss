<%-- admintweaks override of the core menu header (admintweaks#71). With SystemSwitcher.systems configured it
     renders the CMS system switcher; without, $AdminCoreMenuLogo renders core's OWN template file (not a copy),
     so the header stays exactly as core ships it on every framework version. --%>
<% if $AdminSystemSwitcher %><% with $AdminSystemSwitcher %><% include Restruct\\Silverstripe\\AdminTweaks\\SystemSwitcher %><% end_with %><% else %>$AdminCoreMenuLogo<% end_if %>
