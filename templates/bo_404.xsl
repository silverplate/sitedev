<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "character_entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" indent="no" encoding="utf-8" />

	<xsl:include href="common.xsl" />
	<xsl:include href="bo_common.xsl" />

	<xsl:template match="page">
		<html>
			<head>
				<!--link href="/favicon.ico" type="image/x-icon" rel="shortcut icon" />
				<link href="/favicon.ico" type="image/x-icon" rel="icon" /-->

				<title>
					<xsl:text>Страница не найдена - Система управления</xsl:text>
					<xsl:for-each select="system/title">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="text()" disable-output-escaping="yes" />
					</xsl:for-each>
				</title>

				<link href="/cms/f/css/main.css" type="text/css" rel="stylesheet" />
			</head>
			<body>
				<table width="100%" height="100%">
					<tr>
						<td height="99%" valign="top">
							<xsl:call-template name="page_navigation" />
							<div id="content">
								<div id="title"><h1>Страница не найдена</h1></div>
								<p class="text">
									Страница <xsl:call-template name="get_page_link" /> не&nbsp;найдена.
									Если&nbsp;вы&nbsp;уверены, что&nbsp;произошла ошибка, пожалуйста, сообщите о&nbsp;ней
									по&nbsp;адресу <a href="mailto:support@sitedev.ru">support@sitedev.ru</a>.
								</p>
							</div>
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
</xsl:stylesheet>
