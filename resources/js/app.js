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
    AlertTriangle,
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
    Target,
    Image
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
    AlertTriangle,
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
    Target,
    Image
};

window.lucide = {
    createIcons: () => createIcons({ icons: appIcons })
};

const initIcons = () => {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
};

document.addEventListener('DOMContentLoaded', initIcons);
document.addEventListener('livewire:navigated', initIcons);

// Ensure icons re-render on any Livewire update / morph
const setupLivewireHooks = () => {
    if (window.Livewire) {
        initIcons();
        window.Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                queueMicrotask(initIcons);
                setTimeout(initIcons, 50);
            });
        });
        window.Livewire.hook('morph.updated', () => {
            queueMicrotask(initIcons);
        });
    }
};

document.addEventListener('livewire:init', setupLivewireHooks);

// Fallback in case livewire:init fired before script ran
if (window.Livewire) {
    setupLivewireHooks();
}
