local source_path = arg[1] or '/etc/rspamd/lua/rspamd.local.lua'
local file = assert(io.open(source_path, 'r'))
local source = file:read('*a')
file:close()

local implementation = assert(source:match('(local function expand_footer%b().-\nend)\n\nrspamd_config:register_symbol'))
local expand_footer = assert(loadstring(implementation .. '\nreturn expand_footer'))()

local function check(template, variables, expected)
  local actual = expand_footer(template, variables)
  assert(actual == expected, string.format('footer mismatch: expected %q, got %q', expected, actual))
end

check('Hello {= from_name =}, {= unknown =}', {from_name = 'Thomas'}, 'Hello Thomas, ')
check('{% if Abteilung %}<b>{= Abteilung =}</b>{% endif %}', {Abteilung = 'IT'}, '<b>IT</b>')
check('{% if Abteilung %}<b>{= Abteilung =}</b>{% endif %}', {}, '')
check('Tel.: {% if Telefon %}{= Telefon =}{% else %}default{% endif %}', {}, 'Tel.: default')
check('Tel.: {% if Telefon %}{= Telefon =}{% else %}default{% endif %}', {Telefon = '123'}, 'Tel.: 123')
check('{% if Mobil %}Mobil: {= Mobil =}\n{% endif %}E-Mail: {= from_addr =}',
  {from_addr = '3CX@drk-rmt.org'}, 'E-Mail: 3CX@drk-rmt.org')
check('{% if Abteilung %}A{% if Telefon %}B{% else %}C{% endif %}{% else %}D{% endif %}',
  {Abteilung = 'IT'}, 'AC')
check('{% if dangerous() %}literal{% endif %}', {}, '{% if dangerous() %}literal{% endif %}')
print('Domain footer regression tests passed')
