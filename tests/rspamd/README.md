# Domain footer regression check

Run after merging an upstream mailcow release, before restarting Rspamd. The
fork's legacy `{% if key %}` footer support lives in `rspamd.local.lua`; upstream
may change this function during an update. A passing Rspamd config test alone
does not check rendered domain signatures.

On the mailcow host, from `/opt/mailcow-dockerized`:

```sh
docker cp tests/rspamd/test_domain_footer.lua mailcowdockerized-rspamd-mailcow-1:/tmp/test_domain_footer.lua
docker exec mailcowdockerized-rspamd-mailcow-1 rspamadm lua -s /tmp/test_domain_footer.lua -a /etc/rspamd/lua/rspamd.local.lua
```

The test extracts the installed renderer and checks the actual plain/HTML
conditional forms without processing a real message or changing mail state.
It also checks that the message's display name wins and that the mailbox-name
fallback is limited to the authenticated sender mailbox.
