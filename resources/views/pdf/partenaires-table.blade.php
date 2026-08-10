<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Type</th>
            <th>Consultant</th>
            <th>Entité Commerciale</th>
            <th>Date Création</th>
        </tr>
    </thead>
    <tbody>
        @foreach($partenaires as $partenaire)
        <tr>
            <td>{{ $partenaire->id }}</td>
            <td>{{ $partenaire->nom ?? 'N/A' }}</td>
            <td>{{ $partenaire->email ?? 'N/A' }}</td>
            <td>{{ $partenaire->telephone ?? 'N/A' }}</td>
            <td>{{ $partenaire->statut ?? 'N/A' }}</td>
            <td>{{ $partenaire->type ?? 'N/A' }}</td>
            <td>{{ $partenaire->consultant?->name ?? 'N/A' }}</td>
            <td>{{ $partenaire->entiteCommerciale?->nom ?? 'N/A' }}</td>
            <td>{{ $partenaire->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
