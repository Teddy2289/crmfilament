<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GraphQL Playground — CRM API</title>
    <link rel="stylesheet" href="https://unpkg.com/graphiql@3/graphiql.min.css" />
    <style>
        body { margin: 0; height: 100vh; }
        #app { height: 100vh; }
    </style>
</head>
<body>
    <div id="app"></div>

    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/graphiql@3/graphiql.min.js"></script>

    <script>
        const GRAPHQL_ENDPOINT = '{{ $endpoint }}';

        const fetcher = GraphiQL.createFetcher({
            url: GRAPHQL_ENDPOINT,
            headers: {
                // The Authorization header can be set via the GraphiQL headers editor
            },
        });

        ReactDOM.createRoot(document.getElementById('app')).render(
            React.createElement(GraphiQL, {
                fetcher,
                defaultEditorToolsVisibility: true,
            })
        );
    </script>
</body>
</html>
