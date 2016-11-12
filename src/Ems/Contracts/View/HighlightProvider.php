<?php

namespace Ems\Contracts\View;

/**
 * The HighlightProvider solves the issue you always
 * face when showing the latest news, customers, testimonials,...
 * on your page. It is usually in a sidebar or something.
 * But instead of manually writing the template logic, extend
 * your repository and so on you can use the highlight
 * interfaces.
 * You just have to implement the HighlightItemProvider
 * interface to provide your models from database and the rest is done
 * via the (usually not changing) classes.
 * Bind your HighlightProviders to a Facade (Ems\View\Support\ExtendableFacade)
 * to allow News::latest(5) (returns a Highlight)
 * The Highlight is Traversable so you get your items with:
 * foreach (News::latest(5) as $news)
 * The Highlight is Renderable so you can render the items:
 * echo News::latest(5)->render('news.latest').
 **/
interface HighlightProvider
{
    /**
     * Return the latest items.
     *
     * @param int $limit (optional)
     *
     * @return \Ems\Contracts\View\Highlight
     **/
    public function latest($limit = null);

    /**
     * Return the top items (whatever that means in your resource).
     *
     * @param int $limit (optional)
     *
     * @return \Ems\Contracts\View\Highlight
     **/
    public function top($limit = null);

    /**
     * Return just some random $limit items.
     *
     * @param int $limit (optional)
     *
     * @return \Ems\Contracts\View\Highlight
     **/
    public function some($limit = null);
}
