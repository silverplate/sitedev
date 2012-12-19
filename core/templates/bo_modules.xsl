<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="module[@type = 'tree' or @type = 'simple']">
		<table class="module">
			<tr>
				<td class="title">
					<xsl:if test="not(title or ../update_status)">
						<xsl:attribute name="colspan">2</xsl:attribute>
					</xsl:if>

					<h1><xsl:call-template name="get_page_title" /></h1>
					<xsl:if test="@is_able_to_add"><xsl:call-template name="module_add_element_link" /></xsl:if>

					<xsl:if test="@is_simple_sort">
						<xsl:text>&nbsp;&bull; </xsl:text>
						<xsl:call-template name="module_element_link">
							<xsl:with-param name="label">Сортировать</xsl:with-param>
							<xsl:with-param name="uri">sort.php</xsl:with-param>
							<xsl:with-param name="is_selected">
								<xsl:choose>
									<xsl:when test="contains(/node()/url/@path, 'sort.php')">true</xsl:when>
									<xsl:otherwise>false</xsl:otherwise>
								</xsl:choose>
							</xsl:with-param>
						</xsl:call-template>
					</xsl:if>
				</td>

				<xsl:if test="title or ../update_status">
					<td class="subtitle">
						<xsl:choose>
							<xsl:when test="title and ../update_status">
								<table width="100%">
									<tr valign="top">
										<td width="99%"><xsl:apply-templates select="title" mode="subtitle" /></td>
										<td width="1%"><xsl:apply-templates select="../update_status" /></td>
									</tr>
								</table>
							</xsl:when>
							<xsl:when test="title">
								<xsl:apply-templates select="title" mode="subtitle" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:apply-templates select="../update_status" />
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</xsl:if>
			</tr>

			<tr>
				<td class="navigation">
					<xsl:choose>
						<xsl:when test="@type = 'tree'">
							<div id="tree_list" class="tree" />
							<xsl:variable name="module_name" select="@name" />
							<xsl:variable name="field_name">navigation</xsl:variable>

							<script type="text/javascript" language="JavaScript">
								<xsl:value-of select="concat('var formTreeValues_', $field_name, ' = new Array(')" />
								<xsl:if test="@id"><xsl:value-of select="concat('&quot;', @id, '&quot;')" /></xsl:if>
								<xsl:value-of select="concat('); treeLoad(&quot;tree_list&quot;, &quot;', $module_name, '&quot;, &quot;', $field_name, '&quot;, &quot;&quot;, &quot;list&quot;);')" />
							</script>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="local-navigation|local_navigation" mode="list" />
						</xsl:otherwise>
					</xsl:choose>
				</td>
				<td class="content">
					<xsl:for-each select="local-navigation[@type = 'content_filter']|local_navigation[@type = 'content_filter']">
						<xsl:variable name="is_date">
							<xsl:choose>
								<xsl:when test="@is-date or @is_date">true</xsl:when>
								<xsl:otherwise>false</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>

						<div id="filter_content" />
						<script type="text/javascript" language="JavaScript"><xsl:value-of select="concat('filter_update(&quot;filter_content&quot;, false, false, ', $is_date, ');')" /></script>
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

	<xsl:template match="document_data">
		<a
			onclick="open_window('data.php?parent_id={ancestor::node()[name() = 'module']/@id}')"
			class="add_element new_window_link"
			style="margin-bottom: 40px;"
		>Добавить</a>
		<br clear="all" />

		<input type="hidden" id="wysiwyg_file_path" value="{ancestor::node()[name() = 'module']/@file_path}" />
		<div id="document_data_blocks" />

		<script type="text/javascript" language="JavaScript">
			function documentUpdateDataBlocks() {
				documentDataUpdateBranch('document_data_blocks', '<xsl:value-of select="ancestor::node()[name() = 'module']/@id" />');
			}
			documentUpdateDataBlocks();
		</script>
	</xsl:template>

	<xsl:template match="upload_file">
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
