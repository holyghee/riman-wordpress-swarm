"""
SemanticMatcher Package
Advanced ML-based content-to-image mapping system
"""

from .core import SemanticMatcher, SemanticMatch, GERMAN_TERM_MAPPING
from .batch_processor import BatchProcessor
from .evaluation import MatchEvaluator

__version__ = '1.0.0'
__all__ = ['SemanticMatcher', 'SemanticMatch', 'GERMAN_TERM_MAPPING', 'BatchProcessor', 'MatchEvaluator']