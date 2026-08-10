<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Consultant</th>
            <th>Entité Commerciale</th>
            <th>Date Création</th>
        </tr>
    </thead>
    <tbody>
        @foreach($clients as $client)
        <tr>
            <td>{{ $client->id }}</td>
            <td>{{ $client->nom ?? 'N/A' }}</td>
            <td>{{ $client->email ?? 'N/A' }}</td>
            <td>{{ $client->telephone ?? 'N/A' }}</td>
            <td>{{ $client->statut ?? 'N/A' }}</td>
            <td>{{ $client->consultant?->name ?? 'N/A' }}</td>
            <td>{{ $client->entiteCommerciale?->nom ?? 'N/A' }}</td>
            <td>{{ $client->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
