<%-- CMS system switcher (admintweaks#71), rendered in place of the core menu header. Scope: SystemSwitcher.
     Every configured string is escaped: the SystemSwitcher getters are cast as Text, ArrayData strings get the
     default Text casting, and attribute values use .ATT. Behaviour (open/close, Escape, click outside) is entwine
     in client/src/js/admintweaks.js; the expanded label is core's .cms-sitename__title, so the collapsed menu
     hides it exactly like core and shows the initials instead.
     The environment is its own badge after the name (admintweaks#79): only the name truncates, so the badge is
     never cut off. Outside .cms-sitename__title on purpose, so it also shows in the collapsed menu. No badge
     element at all where the environment has no suffix (live). aria-label/title keep the full label. --%>
<div class="cms-sitename at-system-switcher">
    <% if $OtherSystems %>
        <button type="button" class="at-system-switcher__toggle" aria-expanded="false" aria-controls="at-system-switcher__list"
                aria-label="$CurrentLabel.ATT" title="$CurrentLabel.ATT">
            <span class="at-system-switcher__initials" aria-hidden="true">$CurrentInitials</span>
            <span class="cms-sitename__title at-system-switcher__label">$CurrentName</span>
            <% if $EnvironmentBadge %><span class="at-system-switcher__env" aria-hidden="true">$EnvironmentBadge</span><% end_if %>
            <span class="at-system-switcher__caret font-icon-caret-down-two" aria-hidden="true"></span>
        </button>
        <ul class="at-system-switcher__list" id="at-system-switcher__list" hidden>
            <% loop $OtherSystems %>
                <%-- Same tab on purpose: switching moves you to the other CMS; ctrl/cmd/middle-click still opens a new tab --%>
                <li><a class="at-system-switcher__link" href="$URL.ATT" data-system="$Key.ATT">$Display</a></li>
            <% end_loop %>
        </ul>
    <% else %>
        <%-- Only the current system is configured: name it, nothing to switch to --%>
        <span class="at-system-switcher__name" title="$CurrentLabel.ATT">
            <span class="at-system-switcher__initials" aria-hidden="true">$CurrentInitials</span>
            <span class="cms-sitename__title at-system-switcher__label">$CurrentName</span>
            <% if $EnvironmentBadge %><span class="at-system-switcher__env">$EnvironmentBadge</span><% end_if %>
        </span>
    <% end_if %>
</div>
