import Chart from 'chart.js/auto';
import {
    createIcons,
    LayoutDashboard,
    Database,
    UploadCloud,
    FolderKanban,
    History,
    GraduationCap,
    SlidersHorizontal,
    TrendingUp,
    CalendarDays,
    Settings,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Clock,
    Flame,
    Award,
    BookOpen,
    Brain,
    Calculator,
    ChevronLeft,
    ChevronRight,
    Search,
    Filter,
    Plus,
    Download,
    Eye,
    Edit3,
    Trash2,
    RotateCcw,
    LogOut,
    Menu,
    X,
    HelpCircle,
    ArrowRight,
    Flag,
    Check,
    FileSpreadsheet,
    FileText,
    Percent,
    Target
} from 'lucide';

window.Chart = Chart;

const appIcons = {
    LayoutDashboard,
    Database,
    UploadCloud,
    FolderKanban,
    History,
    GraduationCap,
    SlidersHorizontal,
    TrendingUp,
    CalendarDays,
    Settings,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Clock,
    Flame,
    Award,
    BookOpen,
    Brain,
    Calculator,
    ChevronLeft,
    ChevronRight,
    Search,
    Filter,
    Plus,
    Download,
    Eye,
    Edit3,
    Trash2,
    RotateCcw,
    LogOut,
    Menu,
    X,
    HelpCircle,
    ArrowRight,
    Flag,
    Check,
    FileSpreadsheet,
    FileText,
    Percent,
    Target
};

window.lucide = {
    createIcons: () => createIcons({ icons: appIcons })
};

document.addEventListener('DOMContentLoaded', () => {
    window.lucide.createIcons();
});

document.addEventListener('livewire:navigated', () => {
    window.lucide.createIcons();
});

// Re-init icons when Livewire component updates
if (window.Livewire) {
    window.Livewire.hook('commit', () => {
        setTimeout(() => {
            window.lucide.createIcons();
        }, 50);
    });
}
