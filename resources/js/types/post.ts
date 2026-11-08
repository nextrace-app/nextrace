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

/** A post on its own page, with the body already rendered to HTML. */
export type PostPage = Post & {
    body_html: string;
};
