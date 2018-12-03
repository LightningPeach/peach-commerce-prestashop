<head>
    {block name='head'}
    {include file='_partials/head.tpl'}
    {/block}
</head>
<body>
{hook h='displayAfterBodyOpeningTag'}
<main>
    <header id="header">
        {block name='header'}
        {include file='_partials/header.tpl'}
        {/block}
    </header>
    <section id="wrapper">
        <div class="container">
            <section id="main">
                <section id="content" class="page-content card card-block">
                    {include file='_partials/breadcrumb.tpl'}
                    <h2>{l s='Error in Lightning Hub' mod='lightninghub'}</h2>
                    <div class="table-responsive-row clearfix">
                        <p>
                            {$error}
                        </p>
                        <a href="javascript: history.go(-1)">Back</a>
                    </div>
                </section>
            </section>
        </div>
    </section>
    <footer id="footer">
        {block name='footer'}
        {include file='_partials/footer.tpl'}
        {/block}
    </footer>
    {block name='javascript_bottom'}
    {include file='_partials/javascript.tpl' javascript=$javascript.bottom}
    {/block}
    {hook h='displayBeforeBodyClosingTag'}
</main>
</body>
