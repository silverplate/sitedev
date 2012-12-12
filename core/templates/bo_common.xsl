<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "character_entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template name="page_navigation">
		<table width="100%">
			<tr>
				<td id="logo"><a href="/"><xsl:value-of select="/node()/system/title/text()" disable-output-escaping="yes" /></a></td>

				<td id="navigation">
					<xsl:apply-templates select="/node()/system/user" />
					<!--xsl:call-template name="navigation_item">
						<xsl:with-param name="uri">/cms/</xsl:with-param>
						<xsl:with-param name="title">Система управления</xsl:with-param>
						</xsl:call-template-->

					<xsl:for-each select="/node()/content/cms_sections/item">
                        <xsl:call-template name="navigation_item">
                            <xsl:with-param name="uri" select="@uri" />
                            <xsl:with-param name="title" select="title/text()" />
                        </xsl:call-template>
					</xsl:for-each>
				</td>
			</tr>
		</table>
	</xsl:template>

	<xsl:template match="user">
		<div id="user_info">
			<xsl:value-of select="title" /><br />
			<xsl:apply-templates select="/node()/system/session/workmates[user]" />
			<a href="./?e">Выйти</a>
		</div>
	</xsl:template>

	<xsl:template match="workmates[user]">
		<div class="workmate_warning">
			<xsl:choose>
				<xsl:when test="count(user) = 1">
					<xsl:text>Вместе с&nbsp;вами работает</xsl:text><br />
					<xsl:text>пользователь </xsl:text>
					<xsl:value-of select="user/text()" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>Вместе с&nbsp;вами работают</xsl:text><br />
					<xsl:text>другие пользователи (</xsl:text>
					<xsl:for-each select="user">
						<xsl:value-of select="text()" />
						<xsl:if test="position() != last()">, </xsl:if>
					</xsl:for-each>
					<xsl:text>)</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:text>.</xsl:text>
			<!--xsl:text>. Убедитесь, что&nbsp;вы&nbsp;не&nbsp;радактируете один и&nbsp;тот&nbsp;же материал.</xsl:text-->
		</div>
	</xsl:template>

	<xsl:template name="navigation_item">
		<xsl:param name="uri" />
		<xsl:param name="title" />

		<div>
			<xsl:attribute name="class">
				<xsl:text>nav_item</xsl:text>
				<xsl:if test="starts-with(/node()/url/text(), $uri) and ($uri != '/cms/' or /node()/url/text() = '/cms/')"> selected</xsl:if>
			</xsl:attribute>
			<xsl:choose>
				<xsl:when test="$uri = /node()/url/text()">
					<xsl:value-of select="$title" disable-output-escaping="yes" />
				</xsl:when>
				<xsl:otherwise>
					<a href="{$uri}"><xsl:value-of select="$title" disable-output-escaping="yes" /></a>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="navigation_group">
		<div id="group_{@id}" style="display:none">
			<xsl:if test="item[@selected]">
				<xsl:attribute name="style">display: block</xsl:attribute>
			</xsl:if>
			<div class="group_item">
				<a href="" onclick="$('navigation_groups').show(); $('group_{@id}').hide(); return false;">&laquo; Назад</a>
			</div>
			<xsl:for-each select="item">
				<xsl:call-template name="navigation_item">
					<xsl:with-param name="uri" select="@uri" />
					<xsl:with-param name="title" select="title/text()" />
				</xsl:call-template>
			</xsl:for-each>
		</div>
	</xsl:template>

	<xsl:template name="page_footer">
		<div id="footer">
			<a href="mailto:support@sitedev.ru">@ Система управления сайтом</a>
			<xsl:text>, 2007</xsl:text>
			<xsl:for-each select="/node()/system/title">
				<xsl:text> &middot; </xsl:text>
				<xsl:value-of select="text()" disable-output-escaping="yes" />
			</xsl:for-each>

			<xsl:text> &middot; &copy; </xsl:text>

			<xsl:call-template name="get-date-period">
                <xsl:with-param name="start-year">2007</xsl:with-param>
            </xsl:call-template>
		</div>
	</xsl:template>

	<xsl:template match="cms_sections">
		<table class="cms_sections">
			<xsl:choose>
				<xsl:when test="group">
					<xsl:apply-templates select="group" mode="cms_section" />
					<xsl:if test="item">
						<tr class="last">
							<td colspan="2" class="last">
								<h3>&mdash;</h3>
							</td>
						</tr>
						<xsl:apply-templates select="item[position() mod 2 = 1]" mode="cms_section_list" />
					</xsl:if>
				</xsl:when>
				<xsl:when test="item">
					<xsl:apply-templates select="item[position() mod 2 = 1]" mode="cms_section_list" />
				</xsl:when>
			</xsl:choose>
		</table>
	</xsl:template>

	<xsl:template match="group" mode="cms_section">
		<xsl:if test="item">
			<tr class="last">
				<td colspan="2" class="last">
					<h3>
						<xsl:if test="position() = 1">
							<xsl:attribute name="class">first</xsl:attribute>
						</xsl:if>
						<xsl:value-of disable-output-escaping="yes" select="title" />
					</h3>
				</td>
			</tr>
			<xsl:apply-templates select="item[position() mod 2 = 1]" mode="cms_section_list" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="item" mode="cms_section_list">
		<tr>
			<xsl:if test="position() = last()">
				<xsl:attribute name="class">last</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select="self::node()|following-sibling::node()[1]" mode="cms_section" />
		</tr>
	</xsl:template>

	<xsl:template match="item" mode="cms_section">
		<td>
			<xsl:if test="position() = last() and position() != 1">
				<xsl:attribute name="class">last</xsl:attribute>
			</xsl:if>
			<a href="{@uri}" class="title"><xsl:value-of select="title/text()" disable-output-escaping="yes" /></a>
			<div class="description"><xsl:value-of select="description/text()" disable-output-escaping="yes" /></div>
		</td>
	</xsl:template>

	<xsl:template name="module_add_element_link">
		<xsl:param name="label">Добавить</xsl:param>
		<xsl:param name="uri">./?NEW</xsl:param>

		<xsl:call-template name="module_element_link">
			<xsl:with-param name="label" select="$label" />
			<xsl:with-param name="uri" select="$uri" />
			<xsl:with-param name="is_selected">
				<xsl:choose>
					<xsl:when test="ancestor-or-self::node()[name() = 'module'][@is_new]">true</xsl:when>
					<xsl:otherwise>false</xsl:otherwise>
				</xsl:choose>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="module_element_link">
		<xsl:param name="label" />
		<xsl:param name="uri" />
		<xsl:param name="is_selected">false</xsl:param>

		<a href="{$uri}">
			<xsl:attribute name="class">
				<xsl:text>add_element</xsl:text>
				<xsl:if test="$is_selected = 'true'"> selected</xsl:if>
			</xsl:attribute>
			<xsl:value-of select="$label" disable-output-escaping="yes" />
		</a>
	</xsl:template>

	<xsl:template
        match="local-navigation[@type = 'filter' or @type = 'content_filter']|local_navigation[@type = 'filter' or @type = 'content_filter']"
        mode="list"
    >
		<script type="text/javascript" src="/cms/f/js/filter.js" />

		<div id="filter_link">
			<xsl:if test="@is-open or @is_open"><xsl:attribute name="style">display: none;</xsl:attribute></xsl:if>
			<a onclick="show_filter();">Отфильтровать</a>
		</div>

		<xsl:variable name="is_sortable">
			<xsl:choose>
				<xsl:when test="@is-sortable or @is_sortable">true</xsl:when>
				<xsl:otherwise>false</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="is_date">
			<xsl:choose>
				<xsl:when test="@is-date or @is_date">true</xsl:when>
				<xsl:otherwise>false</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<form id="filter" onsubmit="filter_update('filter_content', true, {$is_sortable}, {$is_date}); return false;">
			<xsl:if test="@is-open or @is_open"><xsl:attribute name="style">display: block;</xsl:attribute></xsl:if>
			<div class="filter_close"><a onclick="hide_filter();">&times;</a></div>
			<xsl:if test="$is_date = 'true'">
                <xsl:call-template name="date_filter" />
            </xsl:if>

			<xsl:if test="@is-title or @is_title">
				<div class="filter_input">
					<label for="filter_title">Название</label>
					<input type="text" name="filter_title" id="filter_title" class="string">
                        <xsl:attribute name="value"><xsl:choose>
                            <xsl:when test="filter-title">
                                <xsl:value-of select="filter-title" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="filter_title" />
                            </xsl:otherwise>
                        </xsl:choose></xsl:attribute>
                    </input>
				</div>
			</xsl:if>

			<xsl:if test="@is-name or @is_name">
				<div class="filter_input">
					<label for="filter_name">Имя</label>
					<input type="text" name="filter_name" id="filter_name" class="string">
                        <xsl:attribute name="value"><xsl:choose>
                            <xsl:when test="filter-name">
                                <xsl:value-of select="filter-name" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="filter_name" />
                            </xsl:otherwise>
                        </xsl:choose></xsl:attribute>
                    </input>
				</div>
			</xsl:if>

			<xsl:if test="@is-email or @is_email">
				<div class="filter_input">
					<label for="filter_email">Электропочта</label>
					<input type="text" name="filter_email" id="filter_email" class="string">
                        <xsl:attribute name="value"><xsl:choose>
                            <xsl:when test="filter-email">
                                <xsl:value-of select="filter-email" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="filter_email" />
                            </xsl:otherwise>
                        </xsl:choose></xsl:attribute>
                    </input>
				</div>
			</xsl:if>

			<xsl:apply-templates select="filter-param[@name and title and item]|filter_param[@name and title and item]" />

			<div style="text-align: right;"><input type="submit" value="Выбрать" /></div>
			<input type="hidden" name="filter_selected_id" value="{ancestor::node()[name() = 'module']/@id}" />
		</form>
		<br clear="all" />

		<xsl:if test="@type = 'filter'">
			<ul id="filter_content"><xsl:if test="@is-sortable or @is_sortable"><xsl:attribute name="class">sortable</xsl:attribute></xsl:if></ul>
			<script type="text/javascript" language="JavaScript"><xsl:value-of select="concat('filter_update(&quot;filter_content&quot;, false, ', $is_sortable, ', ', $is_date, ');')" /></script>
		</xsl:if>
	</xsl:template>

	<xsl:template match="filter-param|filter_param">
		<div class="filter_input">
			<table class="chooser_item">
				<tr>
					<td><input type="checkbox" name="is_filter_{@name}" id="is_filter_{@name}" value="1" onclick="change_element_visibility('filter_{@name}_ele', this.checked);">
						<xsl:if test="@is-selected or @is_selected">
                            <xsl:attribute name="checked">true</xsl:attribute>
                        </xsl:if>
					</input></td>
					<td class="chooser_label">
						<label for="is_filter_{@name}" class="filter_name"><xsl:value-of select="title" disable-output-escaping="yes" /></label>
						<table class="chooser_item" id="filter_{@name}_ele">
							<xsl:if test="not(@is-selected or @is_selected)">
                                <xsl:attribute name="style">display: none;</xsl:attribute>
                            </xsl:if>

							<xsl:for-each select="item"><tr>
								<td><input type="checkbox" name="filter_{parent::node()/@name}[]" id="{generate-id()}" value="{@value}">
									<xsl:if test="(parent::node()/@is-selected and @is-selected) or (parent::node()/@is_selected and @is_selected)">
                                        <xsl:attribute name="checked">true</xsl:attribute>
                                    </xsl:if>
								</input></td>
								<td class="chooser_label"><label for="{generate-id()}"><xsl:value-of select="text()" disable-output-escaping="yes" /></label></td>
							</tr></xsl:for-each>
						</table>
					</td>
				</tr>
			</table>
			<br clear="all" />
		</div>
	</xsl:template>

	<xsl:template name="date_filter">
		<table class="date_filter">
			<tr>
				<td class="label">С</td>
				<td class="form_calendar">
					<input type="hidden" name="filter_from" id="filter_from" value="{@from}" />
					<input type="text" id="filter_from_input" onblur="calendar_parse_input('filter_from');" />
					<button onclick="calendar_switcher('filter_from', event); return false;"><img src="/cms/f/calendar/btn.gif" width="25" height="13" alt="" /></button>
					<script type="text/javascript" language="JavaScript">calendar_init('filter_from');</script>
				</td>
			</tr>
			<tr>
				<td class="label">По</td>
				<td class="form_calendar">
					<input type="hidden" name="filter_till" id="filter_till" value="{@till}" />
					<input type="text" id="filter_till_input" onblur="calendar_parse_input('filter_till');" />
					<button onclick="calendar_switcher('filter_till', event); return false;"><img src="/cms/f/calendar/btn.gif" width="25" height="13" alt="" /></button>
					<script type="text/javascript" language="JavaScript">calendar_init('filter_till');</script>
				</td>
			</tr>
		</table>

		<table class="date_filter_periods" style="margin-bottom: 20px;">
			<tr>
				<td><a onclick="date_filter_from_date('{@today}')">Сегодня</a></td>
				<td><a onclick="date_filter_from_date('{@week}')">Неделя</a></td>
				<td><a onclick="date_filter_from_date('{@month}')">Месяц</a></td>
				<td><a onclick="date_filter_from_date('{@all_from}', '{@all_till}')">&laquo;Все&raquo;</a></td>
			</tr>
		</table>
	</xsl:template>

	<xsl:template match="local-navigation|local_navigation" name="local_navigation" mode="list">
		<ul id="filter_content">
			<xsl:if test="@is_sortable"><xsl:attribute name="class">sortable</xsl:attribute></xsl:if>
			<xsl:choose>
				<xsl:when test="item">
					<xsl:apply-templates select="item" mode="local_navigation">
						<xsl:with-param name="selected_id" select="ancestor::node()[name() = 'module']/@id" />
						<xsl:with-param name="is_sortable"><xsl:if test="@is-sortable or @is_sortable">1</xsl:if></xsl:with-param>
					</xsl:apply-templates>
				</xsl:when>
				<xsl:otherwise>Нет</xsl:otherwise>
			</xsl:choose>
		</ul>

		<xsl:if test="@is_sortable">
			<script type="text/javascript">
			    $(function() {
			        $("#filter_content").sortable({update: itemSort});
			    });
			</script>
		</xsl:if>
	</xsl:template>

	<xsl:template match="item" mode="local_navigation">
		<xsl:param name="selected_id" />
		<xsl:param name="is_sortable">0</xsl:param>

		<li>
			<xsl:if test="$is_sortable = 1"><xsl:attribute name="id">local_item_<xsl:value-of select="position()" /></xsl:attribute></xsl:if>
			<xsl:for-each select="@*[name() = 'xml:lang' or name() = 'prefix']"><xsl:value-of select="concat(., '&nbsp;')" /></xsl:for-each>

			<xsl:choose>
				<xsl:when test="@is-sort-only or @is_sort_only">
					<span>
						<xsl:attribute name="class">
							<xsl:text>sort_only</xsl:text>
							<xsl:choose>
								<xsl:when test="@id = $selected_id"> selected</xsl:when>
								<xsl:when test="@status"> <xsl:value-of select="@status" /></xsl:when>
								<xsl:when test="not(@is_published) and not(@is-published)"> hidden</xsl:when>
							</xsl:choose>
						</xsl:attribute>
						<xsl:value-of select="title[last()]" disable-output-escaping="yes" />
					</span>
				</xsl:when>
				<xsl:otherwise>
					<a href="./?id={@id}">
						<xsl:choose>
							<xsl:when test="@id = $selected_id"><xsl:attribute name="class">selected</xsl:attribute></xsl:when>
							<xsl:when test="@status"><xsl:attribute name="class"><xsl:value-of select="@status" /></xsl:attribute></xsl:when>
							<xsl:when test="not(@is_published) and not(@is-published)"><xsl:attribute name="class">hidden</xsl:attribute></xsl:when>
						</xsl:choose>
						<xsl:value-of select="title[last()]" disable-output-escaping="yes" />
					</a>
				</xsl:otherwise>
			</xsl:choose>

			<xsl:if test="$is_sortable = 1"><input type="hidden" value="{@id}" /></xsl:if>
		</li>
	</xsl:template>

	<xsl:template match="title" mode="subtitle">
		<h1 style="margin: 0; padding: 0"><xsl:value-of select="text()" disable-output-escaping="yes" /></h1>
	</xsl:template>

	<xsl:template match="update_status">
		<div class="form_message">
			<xsl:choose>
				<xsl:when test="@type = 'error'">
					<xsl:attribute name="class">form_message error</xsl:attribute>
					<xsl:choose>
						<xsl:when test="text()"><xsl:value-of select="text()" disable-output-escaping="yes" /></xsl:when>
						<xsl:otherwise>Информация не&nbsp;сохранена<br /><nobr>из-за допущенных</nobr> ошибок</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@type = 'success'">
					<xsl:attribute name="class">form_message success</xsl:attribute>
					<xsl:choose>
						<xsl:when test="text()"><xsl:value-of select="text()" disable-output-escaping="yes" /></xsl:when>
						<xsl:otherwise>Информация сохранена</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
			</xsl:choose>
		</div>
	</xsl:template>

	<xsl:template name="get_page_link">
		<xsl:variable name="url">
			<xsl:call-template name="get_page_url">
				<xsl:with-param name="is_http" select="falses" />
			</xsl:call-template>
		</xsl:variable>
		<a href="http://{$url}"><xsl:value-of select="$url" /></a>
	</xsl:template>
</xsl:stylesheet>
