export type PostCategory = 'news' | 'guide' | 'results' | 'interview';

export type Post = {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    category: PostCategory;
    date: string | null;
    image: string | null;
    image_alt: string | null;
    source_url: string | null;
};

/** An event a post is about, as the post page links to it. */
export type RelatedEvent = {
    slug: string;
    name: string;
    /** The day of the main race, Y-m-d. */
    date: string;
    city: string;
};

/** A post on its own page, with the body already rendered to HTML. */
export type PostPage = Post & {
    body_html: string;
};
