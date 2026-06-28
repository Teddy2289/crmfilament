<?php

return [
    // ── Workflows Prospects ───────────────────────────────────────────────
    
    // Workflow standard CSE (existant)
    ['model_type' => 'prospect', 'code' => 'cse_standard', 'label' => 'Workflow CSE Standard', 'ordre' => 1],
    
    // Workflow B2B - Entreprises > 50 salariés
    ['model_type' => 'prospect', 'code' => 'b2b_entreprise_50plus', 'label' => 'Workflow B2B - Entreprises 50+ salariés', 'ordre' => 2],
    
    // Workflow B2B - PME < 50 salariés
    ['model_type' => 'prospect', 'code' => 'b2b_pme_moins50', 'label' => 'Workflow B2B - PME < 50 salariés', 'ordre' => 3],
    
    // Workflow B2C - Particuliers
    ['model_type' => 'prospect', 'code' => 'b2c_particuliers', 'label' => 'Workflow B2C - Particuliers', 'ordre' => 4],
    
    // Workflow Réactivation client
    ['model_type' => 'prospect', 'code' => 'reactivation_client', 'label' => 'Workflow Réactivation Client', 'ordre' => 5],
    
    // ── Workflows Partenaires ────────────────────────────────────────────
    
    // Workflow onboarding partenaire standard
    ['model_type' => 'partenaire', 'code' => 'onboarding_standard', 'label' => 'Onboarding Partenaire Standard', 'ordre' => 1],
    
    // Workflow onboarding partenaire premium
    ['model_type' => 'partenaire', 'code' => 'onboarding_premium', 'label' => 'Onboarding Partenaire Premium', 'ordre' => 2],
    
    // Workflow renouvellement contrat
    ['model_type' => 'partenaire', 'code' => 'renouvellement_contrat', 'label' => 'Renouvellement Contrat', 'ordre' => 3],
    
    // ── Workflows Opportunités ───────────────────────────────────────────
    
    // Pipeline opportunité standard
    ['model_type' => 'opportunite', 'code' => 'pipeline_standard', 'label' => 'Pipeline Opportunité Standard', 'ordre' => 1],
    
    // Pipeline opportunité express
    ['model_type' => 'opportunite', 'code' => 'pipeline_express', 'label' => 'Pipeline Opportunité Express', 'ordre' => 2],
    
    // Pipeline opportunité complexe
    ['model_type' => 'opportunite', 'code' => 'pipeline_complexe', 'label' => 'Pipeline Opportunité Complexe', 'ordre' => 3],
];
