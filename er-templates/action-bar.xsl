<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="field" mode="action-bar">
	<style scoped="">
		<xsl:text disable-output-escaping="yes">
		.row {
			margin: 1rem;
		}
		.row button {
			width: 15%;
		}
		</xsl:text>
	</style>
	<xsl:if test="allow-new = 'yes'">
		<div class="row">
			<xsl:apply-templates select="sections/section" mode="action-bar-create" />
		</div>
	</xsl:if>
	<xsl:if test="allow-link = 'yes'">
		<div class="row">
			<xsl:apply-templates select="sections/section" mode="action-bar-link" />
		</div>
	</xsl:if>
</xsl:template>

<xsl:template match="sections/section" mode="action-bar-link">
	<xsl:param name="text" select="'Link to '" />
	<button type="button" class="link" data-link="{@handle}">
		<xsl:value-of select="$text" />
		<xsl:value-of select="." />
	</button>
</xsl:template>

<xsl:template match="sections/section" mode="action-bar-create">
	<xsl:param name="text" select="'Create new '" />
	<button type="button" class="create" data-create="{@handle}">
		<xsl:value-of select="$text" />
		<xsl:value-of select="." />
	</button>
</xsl:template>


</xsl:stylesheet>