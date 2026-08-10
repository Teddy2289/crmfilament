<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Source</th>
            <th>Consultant</th>
            <th>Entité Commerciale</th>
            <th>Date Création</th>
        </tr>
    </thead>
    <tbody>
        @foreach($prospects as $prospect)
        <tr>
            <td>{{ $prospect->id }}</td>
            <td>{{ $prospect->nom ?? 'N/A' }}</td>
            <td>{{ $prospect->email ?? 'N/A' }}</td>
            <td>{{ $prospect->telephone ?? 'N/A' }}</td>
            <td>{{ $prospect->statut ?? 'N/A' }}</td>
            <td>{{ $prospect->source ?? 'N/A' }}</td>
            <td>{{ $prospect->consultant?->name ?? 'N/A' }}</td>
            <td>{{ $prospect->entiteCommerciale?->nom ?? 'N/A' }}</td>
            <td>{{ $prospect->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
