import { useFrame } from "@react-three/fiber";
import { useGLTF } from "@react-three/drei";
import { useRef, useState, useEffect } from "react";
import { Group, Vector3 } from "three";

// Path to FPS model
const FPS_PATH = "/assets/fps.glb";

// Helper function to get the correct asset path based on the context (local dev or WordPress)
function getAssetPath(relativePath: string): string {
  // Check if we're in a WordPress environment with settings provided
  if (typeof window !== 'undefined' && 'jackalopesGameSettings' in window) {
    const settings = (window as any).jackalopesGameSettings;
    return `${settings.assetsUrl}${relativePath}`;
  }
  // Fallback for local development
  return relativePath;
}

export function Player() {
  const playerRef = useRef<Group>(null);
  const [modelLoaded, setModelLoaded] = useState(false);
  const [retryCount, setRetryCount] = useState(0);
  const MAX_RETRIES = 3;

  // Initialize position and movement state
  const position = useRef(new Vector3(0, 0, 0));
  const targetPosition = useRef(new Vector3(0, 0, 0));

  // Load the FPS model with retry logic
  useEffect(() => {
    const loadModel = async () => {
      try {
        // Try to load the model
        const modelPath = getAssetPath(FPS_PATH);
        console.log(`Loading FPS model from: ${modelPath} (attempt ${retryCount + 1}/${MAX_RETRIES + 1})`);
        
        // Load and preload the model
        await useGLTF.preload(modelPath);
        setModelLoaded(true);
        console.log("FPS model loaded successfully");
      } catch (error) {
        console.error("Error loading FPS model:", error);
        
        // Retry loading if we haven't reached the maximum retries
        if (retryCount < MAX_RETRIES) {
          console.log(`Retrying model load (attempt ${retryCount + 2}/${MAX_RETRIES + 1})`);
          setRetryCount(retryCount + 1);
        } else {
          console.error("Failed to load FPS model after maximum retries");
        }
      }
    };

    if (!modelLoaded) {
      loadModel();
    }
  }, [modelLoaded, retryCount]);

  // Animation handling
  useFrame(() => {
    if (playerRef.current) {
      // Example: smoothly move towards target position
      position.current.lerp(targetPosition.current, 0.1);
      playerRef.current.position.copy(position.current);
    }
  });

  // Only render if the model is loaded
  if (!modelLoaded && retryCount >= MAX_RETRIES) {
    return null; // Don't render anything if we've failed to load the model
  }

  // Render the player model
  return (
    <group ref={playerRef}>
      {modelLoaded && (
        <primitive
          object={useGLTF(getAssetPath(FPS_PATH)).scene}
          position={[0, 0, 0]}
          scale={1}
        />
      )}
    </group>
  );
} 