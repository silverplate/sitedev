<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "character_entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" indent="no" encoding="utf-8" />

	<xsl:include href="common.xsl" />
	<xsl:include href="bo_common.xsl" />
	<xsl:include href="bo_modules.xsl" />
	<xsl:include href="bo_forms.xsl" />
	<xsl:include href="bo_project_forms.xsl" />

	<xsl:template match="page">
		<html>
			<head>
				<!--link href="/favicon.ico" type="image/x-icon" rel="shortcut icon" />
				<link href="/favicon.ico" type="image/x-icon" rel="icon" /-->

				<title>
					<xsl:if test="url/@path != '/cms/'">
						<xsl:call-template name="get_page_title" />
						<xsl:text> - </xsl:text>
					</xsl:if>
					<xsl:text>Система управления</xsl:text>
					<xsl:for-each select="system/title">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="text()" disable-output-escaping="yes" />
					</xsl:for-each>
				</title>

				<link href="/cms/f/css/main.css" type="text/css" rel="stylesheet" />
				<link href="/cms/f/css/modules.css" type="text/css" rel="stylesheet" />
				<xsl:text disable-output-escaping="yes">&lt;!--[if IE]&gt;</xsl:text>
				<link href="/cms/f/css/modules_ie.css" type="text/css" rel="stylesheet" />
				<xsl:text disable-output-escaping="yes">&lt;![endif]--&gt;</xsl:text>
				<link href="/cms/f/css/forms.css" type="text/css" rel="stylesheet" />
				<link href="/cms/f/calendar/calendar.css" type="text/css" rel="stylesheet" />

				<script src="/cms/f/js/prototype_150.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/tinymce/jscripts/tiny_mce/tiny_mce.js" type="text/javascript" language="javascript" />
				<script src="/cms/f/js/scriptaculous_170/scriptaculous.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/js/wysiwyg.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/js/module_documents.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/js/cookies.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/js/scripts.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/js/tree.js" type="text/javascript" language="JavaScript" />
				<script src="/cms/f/calendar/calendar.js" type="text/javascript" language="JavaScript" />
			</head>
			<body>
				<div id="loading" />

				<table width="100%" height="100%">
					<tr>
						<td height="99%" valign="top">
							<xsl:call-template name="page_navigation" />
							<xsl:apply-templates select="content" />
						</td>
					</tr>
					<tr>
						<td height="1%" valign="bottom">
							<xsl:call-template name="page_footer" />
						</td>
					</tr>
				</table>
			</body>
		</html>
	</xsl:template>

	<xsl:template match="content">
		<div id="content">
			<xsl:choose>
				<xsl:when test="module">
					<xsl:apply-templates select="module" />
				</xsl:when>
				<xsl:otherwise>
					<div id="title"><h1><xsl:call-template name="get_page_title" /></h1></div>
					<xsl:apply-templates select="*[name() = 'update_status']" />
					<xsl:apply-templates select="*[name() != 'update_status']" />
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>
</xsl:stylesheet>