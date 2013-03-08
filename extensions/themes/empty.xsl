<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:output method="html" doctype-system="about:legacy-compat"  encoding="utf-8" indent="yes"/>
<xsl:template match="/page">
	<xsl:apply-templates select="/page/module"/>
</xsl:template>
</xsl:stylesheet>
