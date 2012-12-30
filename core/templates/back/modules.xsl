<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="module[@type = 'tree' or @type = 'simple']">
		<table class="module">
			<tr>
				<td class="title">
					<xsl:if test="not(title or ../update-status)">
						<xsl:attribute name="colspan">2</xsl:attribute>
					</xsl:if>

					<h1><xsl:call-template name="get-page-title" /></h1>
					<xsl:if test="@is-able-to-add"><xsl:call-template name="module-add-element-link" /></xsl:if>

					<xsl:if test="@is-simple-sort">
						<xsl:text>&nbsp;&bull; </xsl:text>
						<xsl:call-template name="module-element-link">
							<xsl:with-param name="label">Сортировать</xsl:with-param>
							<xsl:with-param name="uri">sort.php</xsl:with-param>
							<xsl:with-param name="is-selected">
								<xsl:choose>
									<xsl:when test="contains(/node()/url/@path, 'sort.php')">true</xsl:when>
									<xsl:otherwise>false</xsl:otherwise>
								</xsl:choose>
							</xsl:with-param>
						</xsl:call-template>
					</xsl:if>
				</td>

				<xsl:if test="title or ../update-status">
					<td class="subtitle">
						<xsl:choose>
							<xsl:when test="title and ../update-status">
								<table width="100%">
									<tr valign="top">
										<td width="99%"><xsl:apply-templates select="title" mode="subtitle" /></td>
										<td width="1%"><xsl:apply-templates select="../update-status" /></td>
									</tr>
								</table>
							</xsl:when>
							<xsl:when test="title">
								<xsl:apply-templates select="title" mode="subtitle" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:apply-templates select="../update-status" />
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</xsl:if>
			</tr>

			<tr>
				<td class="navigation">
					<xsl:choose>
						<xsl:when test="@type = 'tree'">
							<div id="tree-list" class="tree" />
							<xsl:variable name="module-name" select="@name" />
							<xsl:variable name="field-name">navigation</xsl:variable>

							<script type="text/javascript" language="JavaScript">
								<xsl:value-of select="concat('var formTreeValues_', $field-name, ' = new Array(')" />
								<xsl:if test="@id"><xsl:value-of select="concat('&quot;', @id, '&quot;')" /></xsl:if>
								<xsl:value-of select="concat('); treeLoad(&quot;tree-list&quot;, &quot;', $module-name, '&quot;, &quot;', $field-name, '&quot;, &quot;&quot;, &quot;list&quot;);')" />
							</script>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="local-navigation" mode="list" />
						</xsl:otherwise>
					</xsl:choose>
				</td>
				<td class="content">
					<xsl:for-each select="local-navigation[@type = 'content_filter']">
						<xsl:variable name="is-date"><xsl:choose>
                            <xsl:when test="@is-date">true</xsl:when>
                            <xsl:otherwise>false</xsl:otherwise>
						</xsl:choose></xsl:variable>

						<div id="filter-content" />
						<script type="text/javascript" language="JavaScript"><xsl:value-of select="concat('filterUpdate(&quot;filter-content&quot;, false, false, ', $is-date, ');')" /></script>
					</xsl:for-each>

					<xsl:apply-templates select="content" mode="module" />
					<xsl:apply-templates select="form" />
				</td>
			</tr>
		</table>
	</xsl:template>

	<xsl:template match="content" mode="module">
		<div style="font-size: 0.84em;">
			<xsl:apply-templates select="*" />
		</div>
	</xsl:template>

	<xsl:template match="document-data">
		<a
			onclick="openWindow('data.php?parent_id={ancestor::node()[name() = 'module']/@id}')"
			class="add-element new-window-link"
			style="margin-bottom: 40px;"
		>Добавить</a>
		<br clear="all" />

		<input type="hidden" id="wysiwyg-file-path" value="{ancestor::node()[name() = 'module']/@file-path}" />
		<div id="document-data-blocks" />

		<script type="text/javascript" language="JavaScript">
			function documentUpdateDataBlocks() {
				documentDataUpdateBranch('document-data-blocks', '<xsl:value-of select="ancestor::node()[name() = 'module']/@id" />');
			}
			documentUpdateDataBlocks();
		</script>
	</xsl:template>

	<xsl:template match="upload-file">
		<xsl:for-each select="text()">
			<p style="font-size: 0.84em;"><xsl:value-of select="." disable-output-escaping="yes" /></p>
			<form method="post" enctype="multipart/form-data">
				<table>
					<tr>
						<td><input type="file" name="file" /></td>
						<td style="padding-left: 10px;"><input type="submit" name="submit" value="Загрузить" /></td>
					</tr>
				</table>
			</form>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
