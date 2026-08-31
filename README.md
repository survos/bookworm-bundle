# BookwormBundle

Bookworm turns versioned Markdown collections into source-grounded Symfony AI
corpora. It discovers Markdown files, preserves heading hierarchy while chunking,
adds stable source metadata, and sends documents through an app-configured indexer.

The input contract is Markdown: `survos/doc-bundle` may generate it and Bookworm
may consume it without coupling the two bundles.

```yaml
survos_bookworm:
    corpora:
        civicrm_user:
            directory: '%kernel.project_dir%/var/bookworm/civicrm/user-en'
            indexer: 'ai.indexer.civicrm_user'
            repository: 'civicrm/documentation/docs/user-en'
            canonical_base_url: 'https://docs.civicrm.org/user/en/latest/'
            max_chunk_size: 4000
```

```bash
bin/console bookworm:index civicrm_user          # dispatch to Messenger
bin/console bookworm:index civicrm_user --sync   # index in this process
bin/console bookworm:status
```

Route `Survos\BookwormBundle\Message\IndexCorpus` to the desired Messenger
transport. Bookworm deliberately does not select an embedding model or vector
store; the configured Symfony AI indexer owns those decisions.

## Optional GitHub fetching

```bash
composer require knplabs/github-api symfony/http-client nyholm/psr7
bin/console bookworm:fetch github.com/symfony/symfony-docs
```

The destination defaults to `var/bookworm/<owner>/<repository>`. GitLab and other
source hosts can be added behind the source boundary without changing indexing.
