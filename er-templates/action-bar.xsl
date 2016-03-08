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
	<xsl:param name="insert" select="false()" />
	<button type="button" class="link" data-link="{@handle}">
		<xsl:if test="$insert = true()">
			<xsl:attribute name="data-insert" />
		</xsl:if>
		<xsl:value-of select="$text" />
		<xsl:value-of select="." />
	</button>
</xsl:template>

<xsl:template match="sections/section" mode="action-bar-create">
	<xsl:param name="text" select="'Create new '" />
	<xsl:param name="insert" select="false()" />
	<button type="button" class="create" data-create="{@handle}">
		<xsl:if test="$insert = true()">
			<xsl:attribute name="data-insert" />
		</xsl:if>
		<xsl:value-of select="$text" />
		<xsl:value-of select="." />
	</button>
</xsl:template>

<xsl:template match="entry" mode="action-bar">
	<xsl:apply-templates select="." mode="action-bar-edit" />
	<xsl:apply-templates select="." mode="action-bar-delete" />
	<xsl:apply-templates select="." mode="action-bar-replace" />
	<xsl:apply-templates select="." mode="action-bar-unlink" />
</xsl:template>

<xsl:template match="entry" mode="action-bar-edit">
	<xsl:param name="text" select="'Edit'" />
	<xsl:if test="../field/allow-edit = 'yes'">
		<a class="edit ignore-collapsible" data-edit="{@id}">
			<xsl:value-of select="$text" />
		</a>
	</xsl:if>
</xsl:template>

<xsl:template match="entry" mode="action-bar-delete">
	<xsl:param name="text" select="'Delete'" />
	<xsl:if test="../field/allow-delete = 'yes'">
		<a class="delete ignore-collapsible" data-delete="{@id}">
			<xsl:value-of select="$text" />
		</a>
	</xsl:if>
</xsl:template>

<xsl:template match="entry" mode="action-bar-replace">
	<xsl:param name="text" select="'Replace'" />
	<xsl:if test="../field/allow-link = 'yes'">
		<a class="replace ignore-collapsible" data-replace="{@id}">
			<xsl:value-of select="$text" />
		</a>
	</xsl:if>
</xsl:template>

<xsl:template match="entry" mode="action-bar-unlink">
	<xsl:param name="text" select="'Remove'" />
	<xsl:if test="../field/allow-link = 'yes' or ../field/allow-delete = 'yes'">
		<a class="unlink ignore-collapsible" data-replace="{@id}">
			<xsl:value-of select="$text" />
		</a>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>