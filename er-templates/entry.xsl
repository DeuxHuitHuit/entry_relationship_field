<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="entry" mode="content">
	<xsl:apply-templates select="*" mode="content" />
</xsl:template>

<xsl:template match="entry/*" mode="content">
	<div>
		<xsl:value-of select="." />
	</div>
</xsl:template>

<xsl:template match="entry" mode="table-view">
	<span>
		<xsl:value-of select="*[1]" />
	</span>
</xsl:template>

</xsl:stylesheet>