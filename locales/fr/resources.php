<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related Entries, a plugin for Dotclear 2.
#
# Copyright (c) 2013 Philippe aka amalgame
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!isset($__resources['help']['relatedEntries']))
{
	$__resources['help']['relatedEntries'] = dirname(__FILE__).'/help/config_help.html';
}
if (!isset($__resources['help']['relatedEntriesposts']))
{
	$__resources['help']['relatedEntriesposts'] = dirname(__FILE__).'/help/posts_help.html';
}
if (!isset($__resources['help']['relatedEntries_post']))
{
	$__resources['help']['relatedEntries_post'] = dirname(__FILE__).'/help/relatedEntries_post.html';
}
?>