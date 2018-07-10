# automagicalinks

This WordPress plugin automagically converts text in the body of your pages whose name corresponds to webpages on your website into links. For example, if you have a webpage entitled "Foo Bar" on your website, when the words "Foo Bar" appear in the text of a page's body, they'll automatically be linked to the "Foo Bar" webpage.

To disable a word or phrase from automagically being linked, define and use escape characters on a per-phrase instance. Or add them to a list of global exclusions on the plugin options page.

If the automagical mode proves too much, you can use start and end delimiters like so: {{link name}}. If a page with that name exists, it will be linked. If it doesn't, the delimiters will be safely removed.

If the delimiters have been used on the site but after a time you want to disable autolinking, untick linking on the plugin options page and leave the plugin active to continue to strip out the delimiters.