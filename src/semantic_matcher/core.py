"""
SemanticMatcher Core ML Implementation
Advanced content-to-image mapping with weighted semantic scoring
"""

import json
import re
import math
from typing import Dict, List, Tuple, Optional, Any
from dataclasses import dataclass
from collections import defaultdict
import logging
from pathlib import Path

# German translation mapping for environmental terms
GERMAN_TERM_MAPPING = {
    'rückbau': ['demolition', 'deconstruction', 'dismantling'],
    'asbest': ['asbestos', 'hazardous materials'],
    'schadstoff': ['hazardous', 'contamination', 'pollutant'],
    'sanierung': ['remediation', 'restoration', 'cleanup'],
    'umwelt': ['environment', 'environmental', 'ecological'],
    'gutachten': ['assessment', 'evaluation', 'expert report'],
    'prüfung': ['testing', 'inspection', 'examination'],
    'sicherheit': ['safety', 'security', 'protection'],
    'arbeitsschutz': ['occupational safety', 'workplace safety'],
    'brandschutz': ['fire protection', 'fire safety'],
    'bauphysik': ['building physics', 'construction physics'],
    'energieberatung': ['energy consulting', 'energy advisory'],
    'schimmelpilz': ['mold', 'fungal', 'moisture damage'],
    'erkundung': ['investigation', 'survey', 'exploration'],
    'altlasten': ['contaminated sites', 'legacy contamination'],
    'boden': ['soil', 'ground', 'earth'],
    'wasser': ['water', 'hydro', 'aquatic'],
    'luft': ['air', 'atmospheric', 'ventilation'],
    'lärm': ['noise', 'acoustic', 'sound'],
    'erschütterung': ['vibration', 'shock', 'tremor']
}

@dataclass
class SemanticMatch:
    """Container for semantic match results"""
    agent_id: int
    quadrant: str
    theme_score: float
    quadrant_score: float
    total_score: float
    confidence: float
    matched_keywords: List[str]
    category_alignment: str
    image_path: str
    
class SemanticMatcher:
    """Advanced ML-based semantic matching for content-to-image mapping"""
    
    def __init__(self, database_path: str, theme_weight: float = 0.4, quadrant_weight: float = 0.6):
        """
        Initialize SemanticMatcher with weighted scoring
        
        Args:
            database_path: Path to Midjourney database JSON
            theme_weight: Weight for theme matching (default 0.4)
            quadrant_weight: Weight for quadrant description matching (default 0.6)
        """
        self.database_path = database_path
        self.theme_weight = theme_weight
        self.quadrant_weight = quadrant_weight
        self.database = None
        self.agents = []
        self.used_images = set()
        
        # Setup logging
        logging.basicConfig(level=logging.INFO)
        self.logger = logging.getLogger(__name__)
        
        # Load and parse database
        self._load_database()
        self._build_search_index()
        
    def _load_database(self) -> None:
        """Load and validate Midjourney database"""
        try:
            with open(self.database_path, 'r', encoding='utf-8') as f:
                self.database = json.load(f)
            
            self.agents = self.database.get('agents', [])
            total_agents = self.database.get('project_info', {}).get('total_agents', 0)
            total_quadrants = self.database.get('project_info', {}).get('total_quadrants', 0)
            
            self.logger.info(f"Loaded database: {total_agents} agents, {total_quadrants} quadrants")
            
        except Exception as e:
            self.logger.error(f"Failed to load database: {e}")
            raise
    
    def _build_search_index(self) -> None:
        """Build inverted index for efficient keyword searching"""
        self.theme_index = defaultdict(list)
        self.quadrant_index = defaultdict(list)
        
        for agent in self.agents:
            agent_id = agent['agent_id']
            theme = agent['theme'].lower()
            
            # Index theme keywords
            theme_words = self._extract_keywords(theme)
            for word in theme_words:
                self.theme_index[word].append(agent_id)
            
            # Index quadrant keywords  
            for quadrant_name, description in agent['quadrants'].items():
                desc_words = self._extract_keywords(description.lower())
                for word in desc_words:
                    self.quadrant_index[word].append((agent_id, quadrant_name))
    
    def _extract_keywords(self, text: str) -> List[str]:
        """Extract meaningful keywords from text"""
        # Remove common stop words and extract meaningful terms
        stop_words = {
            'the', 'is', 'at', 'which', 'on', 'and', 'a', 'to', 'are', 'as', 'an', 
            'or', 'but', 'in', 'with', 'by', 'from', 'up', 'about', 'into', 'through',
            'during', 'before', 'after', 'above', 'below', 'between', 'among', 'of'
        }
        
        # Extract words, remove punctuation, filter stop words
        words = re.findall(r'\b[a-zA-Z]+\b', text.lower())
        keywords = [word for word in words if len(word) > 2 and word not in stop_words]
        
        return keywords
    
    def _translate_german_terms(self, content: str) -> List[str]:
        """Translate German environmental terms to English equivalents"""
        translated_terms = []
        content_lower = content.lower()
        
        for german_term, english_equivalents in GERMAN_TERM_MAPPING.items():
            if german_term in content_lower:
                translated_terms.extend(english_equivalents)
                
        return translated_terms
    
    def _calculate_theme_score(self, content_keywords: List[str], theme: str) -> Tuple[float, List[str]]:
        """Calculate theme matching score with keyword tracking"""
        theme_keywords = self._extract_keywords(theme.lower())
        matched_keywords = []
        
        if not theme_keywords:
            return 0.0, []
        
        matches = 0
        for keyword in content_keywords:
            if keyword in theme_keywords:
                matches += 1
                matched_keywords.append(keyword)
            # Fuzzy matching for similar terms
            elif any(abs(len(keyword) - len(tk)) <= 2 and 
                    self._similarity(keyword, tk) > 0.8 for tk in theme_keywords):
                matches += 0.5
                matched_keywords.append(keyword)
        
        score = matches / len(theme_keywords)
        return min(score, 1.0), matched_keywords
    
    def _calculate_quadrant_score(self, content_keywords: List[str], quadrants: Dict[str, str]) -> Tuple[float, str, List[str]]:
        """Calculate best quadrant matching score"""
        best_score = 0.0
        best_quadrant = 'top_left'
        best_matches = []
        
        for quadrant_name, description in quadrants.items():
            desc_keywords = self._extract_keywords(description.lower())
            
            if not desc_keywords:
                continue
                
            matches = 0
            current_matches = []
            
            for keyword in content_keywords:
                if keyword in desc_keywords:
                    matches += 1
                    current_matches.append(keyword)
                # Semantic similarity matching
                elif any(self._similarity(keyword, dk) > 0.75 for dk in desc_keywords):
                    matches += 0.7
                    current_matches.append(keyword)
            
            score = matches / len(desc_keywords)
            
            if score > best_score:
                best_score = score
                best_quadrant = quadrant_name
                best_matches = current_matches
        
        return min(best_score, 1.0), best_quadrant, best_matches
    
    def _similarity(self, word1: str, word2: str) -> float:
        """Calculate Levenshtein similarity between two words"""
        if len(word1) == 0 or len(word2) == 0:
            return 0.0
        
        # Dynamic programming approach
        d = [[0] * (len(word2) + 1) for _ in range(len(word1) + 1)]
        
        for i in range(len(word1) + 1):
            d[i][0] = i
        for j in range(len(word2) + 1):
            d[0][j] = j
            
        for i in range(1, len(word1) + 1):
            for j in range(1, len(word2) + 1):
                cost = 0 if word1[i-1] == word2[j-1] else 1
                d[i][j] = min(
                    d[i-1][j] + 1,      # deletion
                    d[i][j-1] + 1,      # insertion  
                    d[i-1][j-1] + cost  # substitution
                )
        
        max_len = max(len(word1), len(word2))
        return 1.0 - (d[len(word1)][len(word2)] / max_len)
    
    def _determine_category_alignment(self, content: str, theme: str) -> str:
        """Determine category alignment for German environmental terms"""
        content_lower = content.lower()
        theme_lower = theme.lower()
        
        # Check for specific category mappings
        if any(term in content_lower for term in ['rückbau', 'demolition', 'dismantl']):
            return 'Demolition'
        elif any(term in content_lower for term in ['asbest', 'asbestos', 'hazardous']):
            return 'Hazardous Materials'
        elif any(term in content_lower for term in ['sanierung', 'remediation', 'cleanup']):
            return 'Environmental Remediation'
        elif any(term in content_lower for term in ['sicherheit', 'safety', 'protection']):
            return 'Safety Assessment'
        elif any(term in content_lower for term in ['gutachten', 'assessment', 'evaluation']):
            return 'Expert Assessment'
        elif any(term in content_lower for term in ['umwelt', 'environment', 'ecological']):
            return 'Environmental'
        else:
            return 'General Professional'
    
    def _calculate_confidence(self, theme_score: float, quadrant_score: float, matched_keywords: List[str]) -> float:
        """Calculate confidence score based on multiple factors"""
        # Base confidence from weighted scores
        base_confidence = (theme_score * self.theme_weight) + (quadrant_score * self.quadrant_weight)
        
        # Keyword density boost
        keyword_boost = min(len(matched_keywords) * 0.1, 0.3)
        
        # Penalty for very low individual scores
        penalty = 0.0
        if theme_score < 0.1 or quadrant_score < 0.1:
            penalty = 0.2
        
        confidence = min(base_confidence + keyword_boost - penalty, 1.0)
        return max(confidence, 0.0)
    
    def find_best_matches(self, content: str, num_matches: int = 5, min_confidence: float = 0.3) -> List[SemanticMatch]:
        """
        Find best semantic matches for given content
        
        Args:
            content: Content to match against
            num_matches: Number of top matches to return
            min_confidence: Minimum confidence threshold
            
        Returns:
            List of SemanticMatch objects sorted by total score
        """
        content_keywords = self._extract_keywords(content.lower())
        
        # Add German term translations
        translated_terms = self._translate_german_terms(content)
        content_keywords.extend(translated_terms)
        
        if not content_keywords:
            self.logger.warning("No keywords extracted from content")
            return []
        
        matches = []
        
        for agent in self.agents:
            agent_id = agent['agent_id']
            theme = agent['theme']
            quadrants = agent['quadrants']
            image_paths = agent['image_paths']
            
            # Calculate theme score
            theme_score, theme_matches = self._calculate_theme_score(content_keywords, theme)
            
            # Calculate best quadrant score
            quadrant_score, best_quadrant, quad_matches = self._calculate_quadrant_score(content_keywords, quadrants)
            
            # Combine matched keywords
            all_matches = list(set(theme_matches + quad_matches))
            
            # Calculate weighted total score
            total_score = (theme_score * self.theme_weight) + (quadrant_score * self.quadrant_weight)
            
            # Calculate confidence
            confidence = self._calculate_confidence(theme_score, quadrant_score, all_matches)
            
            # Skip if below confidence threshold
            if confidence < min_confidence:
                continue
            
            # Determine category alignment
            category = self._determine_category_alignment(content, theme)
            
            # Get image path for best quadrant
            image_path = image_paths.get(best_quadrant, '')
            
            # Skip if image already used (duplicate prevention)
            if image_path in self.used_images:
                continue
            
            match = SemanticMatch(
                agent_id=agent_id,
                quadrant=best_quadrant,
                theme_score=theme_score,
                quadrant_score=quadrant_score,
                total_score=total_score,
                confidence=confidence,
                matched_keywords=all_matches,
                category_alignment=category,
                image_path=image_path
            )
            
            matches.append(match)
        
        # Sort by total score (descending) and return top matches
        matches.sort(key=lambda x: x.total_score, reverse=True)
        
        # Mark selected images as used
        selected_matches = matches[:num_matches]
        for match in selected_matches:
            self.used_images.add(match.image_path)
        
        return selected_matches
    
    def get_match_report(self, content: str, matches: List[SemanticMatch]) -> Dict[str, Any]:
        """Generate detailed match report for analysis"""
        if not matches:
            return {
                'content_length': len(content),
                'extracted_keywords': self._extract_keywords(content.lower()),
                'matches_found': 0,
                'best_match': None,
                'confidence_distribution': {},
                'category_distribution': {}
            }
        
        confidence_dist = defaultdict(int)
        category_dist = defaultdict(int)
        
        for match in matches:
            conf_bucket = f"{int(match.confidence * 10) / 10:.1f}-{int(match.confidence * 10) / 10 + 0.1:.1f}"
            confidence_dist[conf_bucket] += 1
            category_dist[match.category_alignment] += 1
        
        return {
            'content_length': len(content),
            'extracted_keywords': self._extract_keywords(content.lower()),
            'translated_terms': self._translate_german_terms(content),
            'matches_found': len(matches),
            'best_match': {
                'agent_id': matches[0].agent_id,
                'total_score': matches[0].total_score,
                'confidence': matches[0].confidence,
                'matched_keywords': matches[0].matched_keywords,
                'category': matches[0].category_alignment
            },
            'confidence_distribution': dict(confidence_dist),
            'category_distribution': dict(category_dist),
            'score_range': {
                'min': min(m.total_score for m in matches),
                'max': max(m.total_score for m in matches),
                'avg': sum(m.total_score for m in matches) / len(matches)
            }
        }
    
    def reset_used_images(self) -> None:
        """Reset the used images set for fresh matching"""
        self.used_images.clear()
        self.logger.info("Reset used images tracking")