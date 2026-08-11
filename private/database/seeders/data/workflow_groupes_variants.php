<?php

return [
    // ── Parcours prospects ───────────────────────────────────────────────
    
    // Parcours standard CSE (existant)
    ['model_type' => 'prospect', 'code' => 'cse_standard', 'label' => 'Parcours CSE standard', 'ordre' => 1],
    
    // Parcours B2B - Entreprises > 50 salariés
    ['model_type' => 'prospect', 'code' => 'b2b_entreprise_50plus', 'label' => 'Parcours B2B - Entreprises 50+ salariés', 'ordre' => 2],
    
    // Parcours B2B - PME < 50 salariés
    ['model_type' => 'prospect', 'code' => 'b2b_pme_moins50', 'label' => 'Parcours B2B - PME < 50 salariés', 'ordre' => 3],
    
    // Parcours B2C - Particuliers
    ['model_type' => 'prospect', 'code' => 'b2c_particuliers', 'label' => 'Parcours B2C - Particuliers', 'ordre' => 4],
    
    // Parcours de réactivation client
    ['model_type' => 'prospect', 'code' => 'reactivation_client', 'label' => 'Parcours de réactivation client', 'ordre' => 5],
    
    // ── Parcours partenaires ────────────────────────────────────────────
    
    // Parcours d'intégration partenaire standard
    ['model_type' => 'partenaire', 'code' => 'onboarding_standard', 'label' => 'Intégration partenaire standard', 'ordre' => 1],
    
    // Parcours d'intégration partenaire premium
    ['model_type' => 'partenaire', 'code' => 'onboarding_premium', 'label' => 'Intégration partenaire premium', 'ordre' => 2],
    
    // Parcours de renouvellement contrat
    ['model_type' => 'partenaire', 'code' => 'renouvellement_contrat', 'label' => 'Renouvellement de contrat', 'ordre' => 3],
    
    // ── Parcours opportunités ───────────────────────────────────────────
    
    // Pipeline opportunité standard
    ['model_type' => 'opportunite', 'code' => 'pipeline_standard', 'label' => 'Pipeline opportunité standard', 'ordre' => 1],
    
    // Pipeline opportunité express
    ['model_type' => 'opportunite', 'code' => 'pipeline_express', 'label' => 'Pipeline opportunité express', 'ordre' => 2],
    
    // Pipeline opportunité complexe
    ['model_type' => 'opportunite', 'code' => 'pipeline_complexe', 'label' => 'Pipeline opportunité complexe', 'ordre' => 3],
];
