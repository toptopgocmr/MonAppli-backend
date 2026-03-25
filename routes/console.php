<?php

use Illuminate\Support\Facades\Schedule;

// Vérifier les documents expirant dans 30 jours — tous les matins à 8h
Schedule::command('toptopgo:check-documents --days=30')->dailyAt('08:00');

// Nettoyer les anciennes positions GPS — chaque nuit à 2h
Schedule::command('toptopgo:clean-locations --days=7')->dailyAt('02:00');

// Rapport quotidien — chaque matin à 7h
Schedule::command('toptopgo:daily-report')->dailyAt('07:00');
