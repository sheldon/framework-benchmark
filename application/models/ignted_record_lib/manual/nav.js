function menu(basepath)
{
	var base = (basepath == 'null' || basepath == undefined) ? '' : basepath;
	
	document.write(
'<table border="0" cellspacing="5" cellpadding="5" id="nav_inner">\n' +
'		<tr>\n' +
'			<td class="td" valign="top">\n' +
'				<h3>Introduction</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'index.html">About IgnitedRecord</a></li>\n' +
'					<li><a href="'+base+'toc.html">Table of Contents</a></li>\n' +
'					<li><a href="'+base+'info/features.html">Features</a></li>\n' +
'					<li><a href="'+base+'info/quick.html">Quick Start</a></li>\n' +
'				</ul>\n' +
'				<h3>Info</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'info/requirements.html">Server Requirements</a></li>\n' +
'					<li><a href="'+base+'info/change_log.html">Change Log</a></li>\n' +
'					<li><a href="'+base+'info/license.html">License</a></li>\n' +
'					<li><a href="'+base+'info/credits.html">Credits</a></li>\n' +
'				</ul>\n' +
'			</td>\n' +
'			<td class="td_sep" valign="top">\n' +
'				<h3>Installation</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'inst/install.html">How To</a></li>\n' +
'					<li><a href="'+base+'inst/php4.html">PHP 4 Specific</a></li>\n' +
'					<li><a href="'+base+'inst/config.html">Configuration</a></li>\n' +
'					<li><a href="'+base+'inst/loading.html">Loading IgnitedRecord</a></li>\n' +
'				</ul>\n' +
'				<h3>Usage</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'use/models.html">Creating Models</a></li>\n' +
'					<li><a href="'+base+'use/fetch.html">Fetching Data</a></li>\n' +
'					<li><a href="'+base+'use/adv_fetch.html">Advanced Filtering</a></li>\n' +
'					<li><a href="'+base+'use/manip.html">Manipulating Data</a></li>\n' +
'					<li><a href="'+base+'use/save.html">Creating, Saving and Deleting Records</a></li>\n' +
'				</ul>\n' +
'			</td>\n' +
'			<td class="td_sep" valign="top">\n' +
'				<h3>Relations</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'rel/belongs_to.html">The Belongs To Relationship</a></li>\n' +
'					<li><a href="'+base+'rel/has_many.html">The Has Many Relationship</a></li>\n' +
'					<li><a href="'+base+'rel/has_one.html">The Has One Relationship</a></li>\n' +
'					<li><a href="'+base+'rel/habtm.html">The Has And Belongs To Many Relationship</a></li>\n' +
'				</ul>\n' +
'				<h3>Using Relations</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'use_rel/conf_rel.html">Configure Relationships</a></li>\n' +
'					<li><a href="'+base+'use_rel/load_rel.html">Loading Related Objects</a></li>\n' +
'					<li><a href="'+base+'use_rel/estab_rel.html">Establish Relationships</a></li>\n' +
'					<li><a href="'+base+'use_rel/rem_rel.html">Remove Relationships</a></li>\n' +
'				</ul>\n' +
'			</td>\n' +
'			<td class="td_sep" valign="top">\n' +
'				<h3>Behaviours</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'behaviours/index.html">Using Act_as</a></li>\n' +
'					<li><a href="'+base+'behaviours/tree.html">The Tree behaviour</a></li>\n' +
'					<li><a href="'+base+'behaviours/thirdparty.html">Thirdparty Behaviours</a></li>\n' +
'					<li><a href="'+base+'behaviours/make.html">How to Make a Behaviour</a></li>\n' +
'					<li><a href="'+base+'behaviours/hooks.html">Hooks / Triggers</a></li>\n' +
'				</ul>\n' +
'				<h3>Misc.</h3>\n' +
'				<ul>\n' +
'					<li><a href="'+base+'misc/iq_subqueries.html">IgnitedQuery Subqueries</a></li>\n' +
'					<li><a href="'+base+'misc/u_print_r.html">Customizable print_r()</a></li>\n' +
'				</ul>\n' +
'			</td>\n' +
'		</tr>\n' +
'	</table>'
	);
}