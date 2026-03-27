import { BrowserRouter as Router, Routes, Route, useParams } from 'react-router-dom';
import SimulationDashboard from './Components/SimulationDashboard';

// A small wrapper to extract the ID from the URL and pass it to your component
function RecommendationWrapper() {
    const { productId } = useParams<{ productId: string }>();
    return <SimulationDashboard productId={productId || 'Unknown'} />;
}

function App() {
  return (
    <Router>
      <div className="min-h-screen bg-slate-50 py-12 px-4">
        <div className="max-w-5xl mx-auto">
          <Routes>
            <Route path="/recommendation/:productId" element={<RecommendationWrapper />} />
            
            {/* Default fallback if you just visit localhost:5173 */}
            <Route path="/" element={
              <div className="text-center p-10 bg-white rounded-xl shadow">
                <h1 className="text-2xl font-bold mb-4">Bayesian Engine Entry Point</h1>
                <p className="text-gray-600">Please visit <code className="bg-gray-100 p-1">/recommendation/P8392</code> to see the simulation.</p>
              </div>
            } />
          </Routes>
        </div>
      </div>
    </Router>
  );
}

export default App;