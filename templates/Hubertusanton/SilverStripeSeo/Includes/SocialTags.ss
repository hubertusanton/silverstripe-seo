<% if not $SEOHideSocialData %>
    <meta property="og:site_name" content="$SiteConfig.Title" />
    <meta property="og:locale" content="$i18nLocale" />

    <meta property="og:title" content="$SEOSocialTitle" />
    <meta name="twitter:title" content="$SEOSocialTitle" />

    <% if $MetaDescription %>
        <meta property="og:description" content="$MetaDescription" />
        <meta name="twitter:description" content="$MetaDescription" />
    <% end_if %>

    <meta property="og:type" content="$SEOSocialType" />
    <meta property="og:url" content="$AbsoluteLink" />

    <% if $SEOPreferedSocialImage.exists %>
        <meta property="og:image" content="$SEOPreferedSocialImage.AbsoluteLink" />
        <meta name="twitter:image" content="$SEOPreferedSocialImage.AbsoluteLink" />
    <% end_if %>
<% end_if %>