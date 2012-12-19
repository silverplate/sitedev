<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "entities.dtd">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="upload_file">
        <xsl:apply-templates select="html" />

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" />
            <xsl:text> </xsl:text>
            <input type="submit" name="submit" value="Загрузить" />
        </form>

        <xsl:if test="error">
            <p style="color: #f00; font-size: 0.84em;">
                <xsl:value-of select="error/text()" disable-output-escaping="yes" />
            </p>
        </xsl:if>
    </xsl:template>

<!--
    <xsl:template match="element" mode="project_form_element">
        <xsl:choose>
            <xsl:when test="@type = 'product_specifications'">
            </xsl:when>
        </xsl:choose>
    </xsl:template>
 -->
</xsl:stylesheet>
