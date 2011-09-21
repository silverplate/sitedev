<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "character_entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="*" mode="html">
		<xsl:element name="{name()}">
			<xsl:apply-templates select="@*" mode="html" />
			<xsl:apply-templates select="text() | *" mode="html" />
		</xsl:element>
	</xsl:template>

	<xsl:template match="@*" mode="html">
		<xsl:attribute name="{name(.)}"><xsl:value-of select="." /></xsl:attribute>
	</xsl:template>

	<xsl:template match="text()" mode="html">
		<xsl:value-of select="." disable-output-escaping="yes" />
	</xsl:template>

	<xsl:template name="get_page_title">
		<xsl:choose>
			<xsl:when test="/node()/content/page_title/text()">
				<xsl:value-of select="/node()/content/page_title/text()" disable-output-escaping="yes" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="/node()/title/text()" disable-output-escaping="yes" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="get_page_url">
		<xsl:param name="is_http">true</xsl:param>

		<xsl:for-each select="/node()/url">
			<xsl:if test="$is_http = 'true'">http://</xsl:if>
			<xsl:value-of select="concat(@host, text())" />
		</xsl:for-each>
	</xsl:template>

	<xsl:template name="get_date_period">
		<xsl:param name="start_year" />

		<xsl:if test="$start_year != /node()/date/@year">
			<xsl:value-of select="$start_year" />
			<xsl:text>&mdash;</xsl:text>
		</xsl:if>

		<xsl:value-of select="/node()/date/@year" />
	</xsl:template>

	<xsl:template match="html">
		<xsl:value-of select="text()" disable-output-escaping="yes" />
	</xsl:template>

    <xsl:template name="html-image">
		<xsl:param name="id" />
		<xsl:param name="alt" />
		<xsl:param name="title" />
		<xsl:param name="style" />

		<img src="{@uri}" width="{@width}" height="{@height}">
			<xsl:if test="$id != ''">
			    <xsl:attribute name="id"><xsl:value-of select="$id" /></xsl:attribute>
			</xsl:if>

            <xsl:if test="$alt != ''">
                <xsl:attribute name="alt"><xsl:value-of select="$alt" /></xsl:attribute>
            </xsl:if>

            <xsl:if test="$title != ''">
                <xsl:attribute name="title"><xsl:value-of select="$title" /></xsl:attribute>
            </xsl:if>

            <xsl:for-each select="@class">
                <xsl:copy select="." />
            </xsl:for-each>

            <xsl:if test="@style or $style != ''">
                <xsl:attribute name="style">
                    <xsl:value-of select="@style" />
                    <xsl:value-of select="$style" />
                </xsl:attribute>
            </xsl:if>
		</img>
    </xsl:template>

	<xsl:template match="image|illu">
		<xsl:param name="id" />
		<xsl:param name="alt" />
		<xsl:param name="title" />
		<xsl:param name="style" />

        <xsl:call-template name="html-image">
            <xsl:with-param name="id">
                <xsl:choose>
                    <xsl:when test="$id">
                        <xsl:value-of select="$id" />
                    </xsl:when>
                    <xsl:when test="@id">
                        <xsl:value-of select="@id" />
                    </xsl:when>
                </xsl:choose>
            </xsl:with-param>

            <xsl:with-param name="alt">
                <xsl:choose>
                    <xsl:when test="$alt">
                        <xsl:value-of select="$alt" />
                    </xsl:when>
                    <xsl:when test="alt">
                        <xsl:value-of select="alt" />
                    </xsl:when>
                    <xsl:when test="@alt">
                        <xsl:value-of select="@alt" />
                    </xsl:when>
                </xsl:choose>
            </xsl:with-param>

            <xsl:with-param name="title">
                <xsl:choose>
                    <xsl:when test="$title">
                        <xsl:value-of select="$title" />
                    </xsl:when>
                    <xsl:when test="title">
                        <xsl:value-of select="title" />
                    </xsl:when>
                    <xsl:when test="@title">
                        <xsl:value-of select="@title" />
                    </xsl:when>
                </xsl:choose>
            </xsl:with-param>

            <xsl:with-param name="style">
                <xsl:choose>
                    <xsl:when test="$style">
                        <xsl:value-of select="$style" />
                    </xsl:when>
                    <xsl:when test="@style">
                        <xsl:value-of select="@style" />
                    </xsl:when>
                </xsl:choose>
            </xsl:with-param>
        </xsl:call-template>
	</xsl:template>

	<xsl:template name="list_navigation">
		<xsl:param name="total" />
		<xsl:param name="per_page" />
		<xsl:param name="page" />
		<xsl:param name="type" />
		<xsl:param name="url_subquery" />
		<xsl:param name="separator" />
		<xsl:param name="is_tiny" />
		<xsl:param name="step" />
		<xsl:param name="js_func" />
		<xsl:param name="lang" />

		<xsl:variable name="total_pages" select="ceiling($total div $per_page)" />
		<xsl:variable name="item_separator">
			<xsl:choose>
				<xsl:when test="$separator">
					<span class="list_navigation_spacer"><xsl:value-of select="$separator" /></span>
				</xsl:when>
				<xsl:otherwise>&nbsp;</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="from" select="floor(($page - 1) div $step) * $step + 1" />

		<xsl:if test="$total_pages &gt; 1">
			<div id="list_navigation">
				<xsl:if test="$page != 1 and $total_pages &gt; $per_page">
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="1" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">|&lt;</xsl:when>
							<xsl:when test="$lang = 'en'">First</xsl:when>
							<xsl:otherwise>В&nbsp;начало</xsl:otherwise>
						</xsl:choose>
					</a>
					<xsl:copy-of select="$item_separator" />
				</xsl:if>
				<xsl:if test="$page - $step &gt;= 1">
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="$page - $step" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">&lt;&lt;</xsl:when>
							<xsl:otherwise>&minus;<xsl:value-of select="$step" /></xsl:otherwise>
						</xsl:choose>
					</a>
					<xsl:copy-of select="$item_separator" />
				</xsl:if>
				<xsl:if test="$page != 1">
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="$page - 1" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">&lt;</xsl:when>
							<xsl:when test="$lang = 'en'">Previous</xsl:when>
							<xsl:otherwise>Предыдущая</xsl:otherwise>
						</xsl:choose>
					</a>
					<xsl:copy-of select="$item_separator" />
				</xsl:if>
				<xsl:call-template name="list_navigation_item">
					<xsl:with-param name="total" select="$total_pages" />
					<xsl:with-param name="page" select="1" />
					<xsl:with-param name="selected" select="$page" />
					<xsl:with-param name="from">
						<xsl:choose>
							<xsl:when test="$total_pages - $page &lt; $step and $from &gt; $step">
								<xsl:value-of select="$total_pages - $step" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$from - 1" />
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
					<xsl:with-param name="type" select="$type" />
					<xsl:with-param name="url_subquery" select="$url_subquery" />
					<xsl:with-param name="separator" select="$separator" />
					<xsl:with-param name="step" select="$step" />
					<xsl:with-param name="js_func" select="$js_func" />
				</xsl:call-template>
				<xsl:if test="$page != $total_pages">
					<xsl:copy-of select="$item_separator" />
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="$page + 1" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">&gt;</xsl:when>
							<xsl:when test="$lang = 'en'">Next</xsl:when>
							<xsl:otherwise>Следующая</xsl:otherwise>
						</xsl:choose>
					</a>
				</xsl:if>
				<xsl:if test="$total_pages - $page &gt;= $step">
					<xsl:copy-of select="$item_separator" />
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="$page + $step" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">&gt;&gt;</xsl:when>
							<xsl:otherwise>+<xsl:value-of select="$step" /></xsl:otherwise>
						</xsl:choose>
					</a>
				</xsl:if>
				<xsl:if test="$total_pages != $page and $total_pages &gt; $per_page">
					<xsl:copy-of select="$item_separator" />
					<a>
						<xsl:attribute name="href">
							<xsl:call-template name="list_navigation_url">
								<xsl:with-param name="page" select="$total_pages" />
								<xsl:with-param name="url_subquery" select="$url_subquery" />
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="js_func" select="$js_func" />
							</xsl:call-template>
						</xsl:attribute>
						<xsl:choose>
							<xsl:when test="$is_tiny = 1">&gt;|</xsl:when>
							<xsl:when test="$lang = 'en'">Last</xsl:when>
							<xsl:otherwise>В&nbsp;конец</xsl:otherwise>
						</xsl:choose>
					</a>
				</xsl:if>
			</div>
			<br style="clear: left" />
		</xsl:if>
	</xsl:template>

	<xsl:template name="list_navigation_item">
		<xsl:param name="total" />
		<xsl:param name="page" />
		<xsl:param name="selected" />
		<xsl:param name="from" />
		<xsl:param name="type" />
		<xsl:param name="separator" />
		<xsl:param name="url_subquery" />
		<xsl:param name="step" />
		<xsl:param name="js_func" />

		<xsl:variable name="current" select="$page + $from" />

		<xsl:choose>
			<xsl:when test="$selected = $current">
				<span class="list_navigation_selected"><xsl:value-of select="$current" /></span>
			</xsl:when>
			<xsl:otherwise>
				<a>
					<xsl:attribute name="href">
						<xsl:call-template name="list_navigation_url">
							<xsl:with-param name="page" select="$current" />
							<xsl:with-param name="type" select="$type" />
							<xsl:with-param name="url_subquery" select="$url_subquery" />
							<xsl:with-param name="js_func" select="$js_func" />
						</xsl:call-template>
					</xsl:attribute>
					<xsl:value-of select="$current" />
				</a>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="$page + $from &lt; $total and $page &lt; $step">
			<xsl:choose>
				<xsl:when test="$separator">
					<span class="list_navigation_spacer"><xsl:value-of select="$separator" /></span>
				</xsl:when>
				<xsl:otherwise>&nbsp;</xsl:otherwise>
			</xsl:choose>

			<xsl:call-template name="list_navigation_item">
				<xsl:with-param name="total" select="$total" />
				<xsl:with-param name="page" select="$page + 1" />
				<xsl:with-param name="selected" select="$selected" />
				<xsl:with-param name="from" select="$from" />
				<xsl:with-param name="type" select="$type" />
				<xsl:with-param name="separator" select="$separator" />
				<xsl:with-param name="url_subquery" select="$url_subquery" />
				<xsl:with-param name="step" select="$step" />
				<xsl:with-param name="js_func" select="$js_func" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template name="list_navigation_url">
		<xsl:param name="page" />
		<xsl:param name="url_subquery" />
		<xsl:param name="type" />
		<xsl:param name="js_func" />

		<xsl:choose>
			<xsl:when test="$js_func != ''">
				<xsl:choose>
					<xsl:when test="contains($js_func, '~')">
						<xsl:text>javascript:</xsl:text>
						<xsl:value-of select="substring-before($js_func, '~')" />
						<xsl:value-of select="$page" />
						<xsl:value-of select="substring-after($js_func, '~')" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="concat('javascript:', $js_func, '(', $page, ')')" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$type = 'query'">
						<xsl:value-of select="concat('?p=', $page)" />
					</xsl:when>
					<xsl:when test="$type = 'url'">
						<xsl:value-of select="concat($page, '.html')" />
					</xsl:when>
				</xsl:choose>

				<xsl:if test="$url_subquery != ''">
					<xsl:choose>
						<xsl:when test="$type = 'query'">
							<xsl:variable name="replaced">
								<xsl:if test="$url_subquery != 'p'">
									<xsl:choose>
										<xsl:when test="contains($url_subquery, 'p=')">
											<xsl:value-of select="substring-before($url_subquery, 'p=')" />
											<xsl:variable name="tail" select="substring-after($url_subquery, 'p=')" />
											<xsl:if test="contains($tail, '&amp;')">
												<xsl:value-of select="substring-after($tail, '&amp;')" />
											</xsl:if>
										</xsl:when>
										<xsl:when test="contains($url_subquery, 'p&amp;')">
											<xsl:value-of select="concat(substring-before($url_subquery, 'p&amp;'),
												substring-after($url_subquery, 'p&amp;'))" />
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="$url_subquery" />
										</xsl:otherwise>
									</xsl:choose>
								</xsl:if>
							</xsl:variable>
							<xsl:if test="$replaced != ''">
								<xsl:value-of select="concat('&amp;', $replaced)" />
							</xsl:if>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="concat('?', $url_subquery)" />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
