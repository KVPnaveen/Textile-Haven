package springboot_backend.service;

import org.springframework.stereotype.Service;
import springboot_backend.dto.ProductSizeDTO;
import springboot_backend.entity.ProductSize;
import springboot_backend.entity.Size;
import springboot_backend.repository.ProductSizeRepository;
import springboot_backend.repository.SizeRepository;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
public class SizeService {

    private final SizeRepository sizeRepository;
    private final ProductSizeRepository productSizeRepository;

    public SizeService(SizeRepository sizeRepository, ProductSizeRepository productSizeRepository) {
        this.sizeRepository = sizeRepository;
        this.productSizeRepository = productSizeRepository;
    }

    public List<Size> getAllSizes() {
        return sizeRepository.findAllByOrderBySortOrderAsc();
    }

    public List<ProductSizeDTO> getProductSizes(Long productId) {
        List<Size> allSizes = sizeRepository.findAllByOrderBySortOrderAsc();
        List<ProductSize> existingProductSizes = productSizeRepository.findByProductId(productId);
        Map<Long, ProductSize> productSizeMap = existingProductSizes.stream()
                .collect(Collectors.toMap(ps -> ps.getSize().getId(), ps -> ps, (a, b) -> a));

        List<ProductSizeDTO> result = new ArrayList<>();
        for (Size size : allSizes) {
            ProductSize ps = productSizeMap.get(size.getId());
            int stock = ps != null ? ps.getStockQuantity() : 0;
            Long psId = ps != null ? ps.getId() : null;
            result.add(new ProductSizeDTO(psId, size.getId(), size.getName(), size.getDisplayName(), size.getSortOrder(), stock));
        }
        return result;
    }
}

